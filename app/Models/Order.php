<?php

namespace App\Models;

use App\Casts\GoldWeightCast;
use App\Casts\RialAmountCast;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'price_per_gram',
        'total_weight',
        'remaining_weight',
        'fee',
        'status',
    ];

    protected $casts = [
        'price_per_gram'   => RialAmountCast::class,
        'total_weight'     => GoldWeightCast::class,
        'remaining_weight' => GoldWeightCast::class,
        'fee'              => RialAmountCast::class,
        'status'           => OrderStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }
}
