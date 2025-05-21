<?php

namespace App\Services;

use App\DataTransferObjects\PlaceOrderData;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Events\OrderPlaced;
use App\Exceptions\OrderCannotBeCancelledException;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\ValueObjects\GoldWeight;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly FeeCalculator      $feeCalculator,
        private readonly WalletService      $walletService,
        private readonly GoldHoldingService $goldService
    )
    {
    }

    public function place(PlaceOrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $user = User::lockForUpdate()->findOrFail($data->userId->value());

            $totalPrice = $data->pricePerGram->multiplyByWeight($data->weight);

            if ($data->type->isBuy()) {
                $this->walletService->ensureSufficientFunds($user, $totalPrice);
            } else {
                $this->goldService->ensureSufficientGold($user, $data->weight);
            }

            $order = Order::create([
                'user_id'          => $data->userId->value(),
                'type'             => $data->type,
                'price_per_gram'   => $data->pricePerGram,
                'total_weight'     => $data->weight,
                'total_price'      => $totalPrice,
                'remaining_weight' => $data->weight,
                'status'           => OrderStatus::Open,
            ]);

            $this->matchOrder($order);

            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    private function matchOrder(Order $incomingOrder): void
    {
        $matches = Order::query()
            ->where('type', $incomingOrder->type->getOpposite())
            ->where('price_per_gram', $incomingOrder->price_per_gram)
            ->where('remaining_weight', '>', 0)
            ->whereIn('status', [OrderStatus::Open, OrderStatus::Partial])
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($matches as $matchedOrder) {
            if ($incomingOrder->remaining_weight->isZero()) break;

            $matchedWeight = $incomingOrder->remaining_weight->min($matchedOrder->remaining_weight);
            $pricePerGram = $incomingOrder->price_per_gram;
            $totalPrice = $pricePerGram->multiplyByWeight($matchedWeight);
            $fee = $this->feeCalculator->calculate($matchedWeight, $pricePerGram);

            $buyerOrder = $incomingOrder->type->isBuy() ? $incomingOrder : $matchedOrder;
            $sellerOrder = $incomingOrder->type->isSell() ? $incomingOrder : $matchedOrder;

            $trade = Trade::create([
                'buy_order_id'   => $buyerOrder->id,
                'sell_order_id'  => $sellerOrder->id,
                'price_per_gram' => $pricePerGram,
                'weight'         => $matchedWeight,
                'total_price'    => $totalPrice,
                'fee'            => $fee,
            ]);

            // Deduct from buyer
            $this->walletService->charge(
                user       : $buyerOrder->user,
                amount     : $totalPrice,
                type       : TransactionType::TRADE,
                description: 'Gold purchase',
                source     : $trade
            );

            $this->walletService->charge(
                user       : $buyerOrder->user,
                amount     : $fee,
                type       : TransactionType::FEE,
                description: 'Trade fee',
                source     : $trade
            );

            // Credit to seller
            $this->walletService->deposit(
                user       : $sellerOrder->user,
                amount     : $totalPrice,
                type       : TransactionType::TRADE,
                description: 'Gold sale',
                source     : $trade
            );

            // Transfer gold
            $this->goldService->deduct(
                user       : $sellerOrder->user,
                amount     : $matchedWeight,
                type       : TransactionType::TRADE,
                description: 'Gold sold',
                source     : $trade
            );

            $this->goldService->add(
                user       : $buyerOrder->user,
                amount     : $matchedWeight,
                type       : TransactionType::TRADE,
                description: 'Gold bought',
                source     : $trade
            );

            // Update order weights and statuses
            $this->updateOrderWeight($incomingOrder, $matchedWeight);
            $this->updateOrderWeight($matchedOrder, $matchedWeight);
        }
    }

    private function updateOrderWeight(Order $order, GoldWeight $matchedWeight): void
    {
        $order->remaining_weight = $order->remaining_weight->subtract($matchedWeight);

        $order->status = $order->remaining_weight->isZero()
            ? OrderStatus::Filled
            : OrderStatus::Partial;

        $order->save();
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            // Reload and lock the order row
            $order->refresh();
            $order->lockForUpdate();

            if (!in_array($order->status, [OrderStatus::Open, OrderStatus::Partial])) {
                throw new OrderCannotBeCancelledException('Only open or partially filled orders can be cancelled.');
            }

            $order->status = OrderStatus::Cancelled;
            $order->save();

            return $order;
        });
    }
}

