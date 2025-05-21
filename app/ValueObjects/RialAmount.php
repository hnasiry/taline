<?php

namespace App\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Stringable;

final readonly class RialAmount implements Stringable
{
    public int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function fromToman(int $toman): self
    {
        return new self($toman * 10);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function toToman(): int
    {
        return (int) floor($this->value / 10);
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(self $other): self
    {
        $result = $this->value - $other->value;

        return new self($result);
    }

    public function multiplyByWeight(GoldWeight $weight): self
    {
        $pricePerGram = BigDecimal::of($this->value);
        $milligrams   = BigDecimal::of($weight->inMilligrams());

        $total = $pricePerGram
            ->multipliedBy($milligrams)
            ->dividedBy(1000, 0, RoundingMode::HALF_UP);

        return new self($total->toInt());
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function greaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function lessThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function format(): string
    {
        return number_format($this->value) . ' ریال';
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
