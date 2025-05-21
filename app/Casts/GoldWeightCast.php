<?php

namespace App\Casts;

use App\ValueObjects\GoldWeight;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class GoldWeightCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): GoldWeight
    {
        return GoldWeight::fromMilligrams($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value instanceof GoldWeight) {
            return $value->inMilligrams();
        }

        // If the value is numeric, assume it's in milligrams
        if (is_numeric($value)) {
            return (new GoldWeight((float) $value, GoldWeight::UNIT_MG))->inMilligrams();
        }

        throw new InvalidArgumentException("Invalid value for GoldWeightCast.");
    }
}
