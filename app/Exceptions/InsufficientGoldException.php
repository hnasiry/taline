<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientGoldException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient available gold.')
    {
        parent::__construct($message);
    }
}
