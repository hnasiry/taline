<?php

namespace App\Enums;

enum TransactionType: string
{
    case TRADE = 'trade';
    case FEE = 'fee';
    case ADJUSTMENT = 'adjustment';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
}
