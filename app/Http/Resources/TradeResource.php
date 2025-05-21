<?php

namespace App\Http\Resources;

use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Trade */
class TradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'price_per_gram' => $this->price_per_gram->toToman(),
            'weight'         => $this->weight->inGrams(),
            'total_price'    => $this->total_price->toToman(),
            'fee'            => $this->fee->toToman(),
            'created_at'     => $this->created_at,
        ];
    }
}
