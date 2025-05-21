<?php

namespace App\Casts;

use App\ValueObjects\RialAmount;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class RialAmountCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): RialAmount
    {
        return new RialAmount($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value instanceof RialAmount) {
            return $value->getValue();
        }

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException('The given value is not a valid RialAmount or integer.');
    }
}
