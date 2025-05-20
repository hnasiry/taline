<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Trade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        return [
            'uuid'           => $this->faker->uuid(),
            'buy_order_id'   => Order::factory()->buy()->create()->id,
            'sell_order_id'  => Order::factory()->sell()->create()->id,
            'weight'         => $this->faker->randomNumber(),
            'price_per_gram' => $this->faker->randomNumber(),
            'total_price'    => $this->faker->randomNumber(),
            'created_at'     => Carbon::now(),
            'updated_at'     => Carbon::now(),
        ];
    }
}
