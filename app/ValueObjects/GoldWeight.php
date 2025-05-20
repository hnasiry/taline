<?php

namespace App\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Stringable;

final class GoldWeight implements Stringable
{
    public const UNIT_MG = 'mg';
    public const UNIT_G = 'g';

    private readonly BigDecimal $milligrams;

    public function __construct(BigDecimal|string|float|int $value, string $unit = self::UNIT_MG)
    {
        $value = BigDecimal::of($value);

        $this->milligrams = match ($unit) {
            self::UNIT_MG      => $value->toScale(0, RoundingMode::HALF_UP),
            self::UNIT_G       => $value->multipliedBy(1000)->toScale(0, RoundingMode::HALF_UP),
            default => throw new InvalidArgumentException("Invalid unit: $unit"),
        };
    }

    public static function fromMilligrams(BigDecimal|string|int $mg): self
    {
        return new self($mg, self::UNIT_MG);
    }

    public static function fromGrams(BigDecimal|string|float $g): self
    {
        return new self($g, self::UNIT_G);
    }

    public function inMilligrams(): BigDecimal
    {
        return $this->milligrams;
    }

    public function inGrams(): BigDecimal
    {
        return $this->milligrams->dividedBy(1000, 6, RoundingMode::HALF_UP);
    }

    public function add(self $other): self
    {
        return self::fromMilligrams($this->milligrams->plus($other->milligrams));
    }

    public function subtract(self $other): self
    {
        return self::fromMilligrams($this->milligrams->minus($other->milligrams));
    }

    public function equals(self $other): bool
    {
        return $this->milligrams->isEqualTo($other->milligrams);
    }

    public function greaterThan(self $other): bool
    {
        return $this->milligrams->isGreaterThan($other->milligrams);
    }

    public function isZero(): bool
    {
        return $this->milligrams->isZero();
    }

    public function format(string $unit = self::UNIT_G): string
    {
        return match ($unit) {
            self::UNIT_MG      => $this->inMilligrams()->toScale(0, RoundingMode::HALF_UP) . ' mg',
            self::UNIT_G       => $this->inGrams()->toScale(3, RoundingMode::HALF_UP) . ' g',
            default => throw new InvalidArgumentException("Invalid unit: {$unit}"),
        };
    }

    public function __toString(): string
    {
        return $this->format(self::UNIT_G);
    }
}
