<?php

namespace App\Models;

use App\Casts\GoldWeightCast;
use App\Casts\RialAmountCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Trade extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'uuid',
        'buy_order_id',
        'sell_order_id',
        'weight',
        'price_per_gram',
        'total_price',
        'fee',
    ];

    protected $casts = [
        'weight'         => GoldWeightCast::class,
        'price_per_gram' => RialAmountCast::class,
        'total_price'    => RialAmountCast::class,
        'fee'            => RialAmountCast::class,
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'source');
    }
}
