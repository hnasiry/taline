<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'uuid'             => $this->faker->uuid(),
            'user_id'          => User::factory(),
            'type'             => $this->faker->randomElement(OrderType::cases()),
            'price_per_gram'   => $this->faker->randomNumber(),
            'total_weight'     => $this->faker->randomNumber(),
            'remaining_weight' => $this->faker->randomNumber(),
            'total_price'      => $this->faker->randomNumber(),
            'status'           => $this->faker->randomElement(OrderStatus::cases()),
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ];
    }

    public function buy(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => OrderType::Buy,
            ];
        });
    }

    public function sell(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => OrderType::Sell,
            ];
        });
    }

    public function open(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => OrderStatus::Open,
            ];
        });
    }
}
