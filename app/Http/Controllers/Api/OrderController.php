<?php

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\PlaceOrderData;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\OrderCannotBeCancelledException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\ValueObjects\GoldWeight;
use App\ValueObjects\RialAmount;
use App\ValueObjects\UserId;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = $user->orders()
            ->latest()
            ->when(
                $request->has('status'),
                fn ($query) => $query->where('status', OrderStatus::tryFrom($request->get('status')))
            );

        return OrderResource::collection(
            $query->paginate($request->get('per_page', 10))
        );
    }

    public function show(string $orderUuid)
    {
        $order = auth()->user()->orders()
            ->where('uuid', $orderUuid)
            ->with(['buyTrades', 'sellTrades'])
            ->firstOrFail();

        $this->authorize('view', $order);

        return new OrderResource($order);
    }

    public function store(OrderRequest $request, OrderService $orderService)
    {
        $order = $orderService->place(
            new PlaceOrderData(
                userId      : new UserId(auth()->id()),
                type        : $request->enum('type', OrderType::class),
                weight      : GoldWeight::fromMilligrams($request->get('weight')),
                pricePerGram: new RialAmount($request->get('price_per_gram'))
            )
        );

        $order->load($order->type->isBuy() ? 'buyTrades' : 'sellTrades');
        return response()->json([
            'data' => new OrderResource($order)
        ], 201);
    }

    public function destroy(string $orderUuid, OrderService $orderService)
    {
        $order = auth()->user()->orders()
            ->where('uuid', $orderUuid)
            ->firstOrFail();

        $this->authorize('delete', $order);

        try {
            $orderService->cancel($order);
        } catch (OrderCannotBeCancelledException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'data'    => new OrderResource($order),
        ]);
    }
}
