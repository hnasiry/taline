<?php

namespace Database\Factories;

use App\Enums\AssetType;
use App\Enums\Direction;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'uuid'        => $this->faker->uuid(),
            'type'        => $this->faker->randomElement(TransactionType::cases()),
            'asset'       => $this->faker->randomElement(AssetType::cases()),
            'direction'   => $this->faker->randomElement(Direction::cases()),
            'amount'      => $this->faker->randomNumber(),
            'description' => $this->faker->text(),
            'source_id'   => null,
            'source_type' => null,
            'created_at'  => Carbon::now(),
            'updated_at'  => Carbon::now(),

            'user_id' => User::factory(),
        ];
    }
}
