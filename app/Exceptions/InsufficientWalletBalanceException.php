<?php
namespace App\Exceptions;

use RuntimeException;

class InsufficientWalletBalanceException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient available wallet balance.')
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error'   => 'insufficient_wallet_balance',
            ], 422);
        }

        return false;
    }
}
