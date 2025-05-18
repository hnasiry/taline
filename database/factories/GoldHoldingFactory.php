<?php

namespace Database\Factories;

use App\Models\GoldHolding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class GoldHoldingFactory extends Factory
{
    protected $model = GoldHolding::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'weight'     => $this->faker->numberBetween(0, 30_000),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
