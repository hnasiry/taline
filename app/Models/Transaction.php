<?php

namespace App\Models;

use App\Enums\AssetType;
use App\Enums\Direction;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'asset',
        'direction',
        'amount',
        'description',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'type'      => TransactionType::class,
        'asset'     => AssetType::class,
        'direction' => Direction::class,
        'amount'    => 'integer',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    protected function signedAmount(): Attribute
    {
        return Attribute::get(fn() => $this->direction === Direction::Credit ? $this->amount : -$this->amount);
    }
}
