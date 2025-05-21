<?php

namespace App\DataTransferObjects;

use App\Enums\OrderType;
use App\ValueObjects\GoldWeight;
use App\ValueObjects\RialAmount;
use App\ValueObjects\UserId;

final readonly class PlaceOrderData
{
    public function __construct(
        public UserId $userId,
        public OrderType $type,
        public GoldWeight $weight,
        public RialAmount $pricePerGram,
    )
    {
    }
}
