<?php

namespace App\Enums;

enum OrderType: string
{
    case Buy = 'buy';
    case Sell = 'sell';

    public function isBuy(): bool
    {
        return $this === self::Buy;
    }

    public function isSell(): bool
    {
        return $this === self::Sell;
    }

    public function getOpposite(): OrderType
    {
        return $this === self::Buy ? self::Sell : self::Buy;
    }
}
