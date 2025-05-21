<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class UserId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer.");
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(UserId $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
