<?php

namespace App\Models;

use App\Casts\RialAmountCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => RialAmountCast::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
