<?php

namespace App\Exceptions;

use RuntimeException;

class OrderCannotBeCancelledException extends RuntimeException
{
    public function __construct(string $message = 'Order can not be cancelled!')
    {
        parent::__construct($message);
    }
}
