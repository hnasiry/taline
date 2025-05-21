<?php
namespace App\Exceptions;

use RuntimeException;

class InsufficientWalletBalanceException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient available wallet balance.')
    {
        parent::__construct($message);
    }
}
