<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\Trade;
use App\ValueObjects\GoldWeight;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    public function __construct(
        private readonly FeeCalculator $feeCalculator,
        private readonly WalletService $walletService,
        private readonly GoldHoldingService $goldService
    ) {}

    public function match(Order $incomingOrder): void
    {
        $matches = $this->findMatchingOrders($incomingOrder);

        foreach ($matches as $matchedOrder) {
            if ($incomingOrder->remaining_weight->isZero()) {
                break;
            }

            $this->processMatch($incomingOrder, $matchedOrder);
        }
    }

    private function findMatchingOrders(Order $order)
    {
        return Order::query()
            ->where('type', $order->type->getOpposite())
            ->where('price_per_gram', $order->price_per_gram->getValue())
            ->where('remaining_weight', '>', 0)
            ->whereIn('status', [OrderStatus::Open, OrderStatus::Partial])
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();
    }

    private function processMatch(Order $incomingOrder, Order $matchedOrder): void
    {
        $matchedWeight = $incomingOrder->remaining_weight->min($matchedOrder->remaining_weight);
        $pricePerGram = $incomingOrder->price_per_gram;
        $totalPrice = $pricePerGram->multiplyByWeight($matchedWeight);
        $fee = $this->feeCalculator->calculate($matchedWeight, $pricePerGram);

        $buyerOrder = $incomingOrder->type->isBuy() ? $incomingOrder : $matchedOrder;
        $sellerOrder = $incomingOrder->type->isSell() ? $incomingOrder : $matchedOrder;

        DB::transaction(function () use ($buyerOrder, $sellerOrder, $matchedWeight, $pricePerGram, $totalPrice, $fee) {
            $trade = $this->createTrade($buyerOrder, $sellerOrder, $matchedWeight, $pricePerGram, $totalPrice, $fee);
            $this->processFunds($buyerOrder, $sellerOrder, $trade, $totalPrice, $fee);
            $this->processGold($buyerOrder, $sellerOrder, $trade, $matchedWeight);
            $this->updateOrderWeights($buyerOrder, $sellerOrder, $matchedWeight);
        });
    }

    private function createTrade(
        Order $buyerOrder,
        Order $sellerOrder,
        GoldWeight $weight,
        $pricePerGram,
        $totalPrice,
        $fee
    ): Trade {
        return Trade::create([
            'buy_order_id'   => $buyerOrder->id,
            'sell_order_id'  => $sellerOrder->id,
            'price_per_gram' => $pricePerGram,
            'weight'         => $weight,
            'total_price'    => $totalPrice,
            'fee'            => $fee,
        ]);
    }

    private function processFunds(
        Order $buyerOrder,
        Order $sellerOrder,
        Trade $trade,
        $totalPrice,
        $fee
    ): void {
        // Deduct from buyer
        $this->walletService->charge(
            user: $buyerOrder->user,
            amount: $totalPrice,
            type: TransactionType::TRADE,
            description: 'Gold purchase',
            source: $trade
        );

        $this->walletService->charge(
            user: $buyerOrder->user,
            amount: $fee,
            type: TransactionType::FEE,
            description: 'Trade fee',
            source: $trade
        );

        // Credit to seller
        $this->walletService->deposit(
            user: $sellerOrder->user,
            amount: $totalPrice,
            type: TransactionType::TRADE,
            description: 'Gold sale',
            source: $trade
        );
    }

    private function processGold(
        Order $buyerOrder,
        Order $sellerOrder,
        Trade $trade,
        GoldWeight $weight
    ): void {
        // Transfer gold from seller to buyer
        $this->goldService->deduct(
            user: $sellerOrder->user,
            amount: $weight,
            type: TransactionType::TRADE,
            description: 'Gold sold',
            source: $trade
        );

        $this->goldService->add(
            user: $buyerOrder->user,
            amount: $weight,
            type: TransactionType::TRADE,
            description: 'Gold bought',
            source: $trade
        );
    }

    private function updateOrderWeights(Order $order1, Order $order2, GoldWeight $matchedWeight): void
    {
        $this->updateOrderWeight($order1, $matchedWeight);
        $this->updateOrderWeight($order2, $matchedWeight);
    }

    private function updateOrderWeight(Order $order, GoldWeight $matchedWeight): void
    {
        $order->remaining_weight = $order->remaining_weight->subtract($matchedWeight);
        $order->status = $order->remaining_weight->isZero()
            ? OrderStatus::Filled
            : OrderStatus::Partial;
        $order->save();
    }
}
