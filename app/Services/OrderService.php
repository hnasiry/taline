<?php

namespace App\Services;

use App\DataTransferObjects\PlaceOrderData;
use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Exceptions\OrderCannotBeCancelledException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly GoldHoldingService $goldService,
        private readonly OrderMatchingService $orderMatchingService
    )
    {}

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

            $this->orderMatchingService->match($order);

            OrderPlaced::dispatch($order);

            return $order;
        });
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

