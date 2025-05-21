<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $trades = null;

        if ($this->type->isBuy() && $this->relationLoaded('buyTrades')) {
            $trades = TradeResource::collection($this->buyTrades);
        }

        if ($this->type->isSell() && $this->relationLoaded('sellTrades')) {
            $trades = TradeResource::collection($this->sellTrades);
        }

        return [
            'uuid'             => $this->uuid,
            'type'             => $this->type,
            'price_per_gram'   => $this->price_per_gram->toToman(),
            'total_weight'     => $this->total_weight->inGrams(),
            'total_price'      => $this->total_price->toToman(),
            'remaining_weight' => $this->remaining_weight->inGrams(),
            'status'           => $this->status,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'trades'           => $trades
        ];
    }
}
