<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientGoldException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient available gold.')
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error'   => 'insufficient_gold',
            ], 422);
        }

        return false;
    }
}
