<?php

namespace App\Services;

use App\ValueObjects\GoldWeight;
use App\ValueObjects\RialAmount;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class FeeCalculator
{
    protected BigDecimal $percentUnder1Gram;
    protected BigDecimal $percentUnder10Gram;
    protected BigDecimal $percentDefault;
    protected int $minFee;
    protected int $maxFee;

    public function __construct(array $config = [])
    {
        $config = array_replace_recursive(config('fee'), $config);

        $this->percentUnder1Gram  = BigDecimal::of($config['percent_by_gram']['lte_1'] ?? '0.02');
        $this->percentUnder10Gram = BigDecimal::of($config['percent_by_gram']['lte_10'] ?? '0.015');
        $this->percentDefault     = BigDecimal::of($config['percent_by_gram']['default'] ?? '0.01');

        $this->minFee = $config['bounds']['min'] ?? 50_000;
        $this->maxFee = $config['bounds']['max'] ?? 5_000_000;
    }

    public function calculate(GoldWeight $weight, RialAmount $pricePerGram): RialAmount
    {
        $grams = $weight->inGramsDecimal();

        $percent = match (true) {
            $grams->isLessThanOrEqualTo(1)  => $this->percentUnder1Gram,
            $grams->isLessThanOrEqualTo(10) => $this->percentUnder10Gram,
            default                         => $this->percentDefault,
        };

        $basePrice = $grams->multipliedBy($pricePerGram->getValue());

        $fee = $basePrice->multipliedBy($percent)->toScale(0, RoundingMode::HALF_UP);

        $boundedFee = max(min($fee->toInt(), $this->maxFee), $this->minFee);

        return new RialAmount($boundedFee);
    }
}
