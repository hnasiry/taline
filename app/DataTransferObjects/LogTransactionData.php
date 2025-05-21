<?php

namespace App\DataTransferObjects;

use App\Enums\AssetType;
use App\Enums\Direction;
use App\Enums\TransactionType;
use App\Models\User;
use App\ValueObjects\UserId;
use Illuminate\Database\Eloquent\Model;

final readonly class LogTransactionData
{
    public function __construct(
        public UserId $userId,
        public int $amount,
        public Direction $direction,
        public TransactionType $type,
        public AssetType $asset,
        public string $description,
        public ?Model $source = null,
    )
    {
    }
}
