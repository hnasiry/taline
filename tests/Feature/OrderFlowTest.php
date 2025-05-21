<?php

use App\Enums\AssetType;
use App\Enums\Direction;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\ValueObjects\GoldWeight;
use App\ValueObjects\RialAmount;
use function Pest\Laravel\actingAs;

it('allows a user to place a buy order and match with sell order', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $buyer->id,
        'asset' => AssetType::Rial,
        'amount' => 10_000_000 * 10,
        'direction' => Direction::Credit,
    ]);

    Transaction::factory()->create([
        'user_id' => $seller->id,
        'asset' => AssetType::Gold,
        'amount' => 1000,
        'direction' => Direction::Credit,
    ]);

    Order::factory()->create([
        'user_id' => $seller->id,
        'type' => OrderType::Sell,
        'price_per_gram' => new RialAmount(10_000_000),
        'total_weight' => GoldWeight::fromMilligrams(1000),
        'remaining_weight' => GoldWeight::fromMilligrams(1000),
        'status' => OrderStatus::Open,
        'total_price' => new RialAmount(10_000_000),
    ]);

    $response = actingAs($buyer)->postJson(route('orders.store'), [
        'type' => 'buy',
        'weight' => 1000,
        'price_per_gram' => 10_000_000,
    ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'data' => [
            'uuid',
            'type',
            'price_per_gram',
            'total_weight',
            'total_price',
            'remaining_weight',
            'status',
            'created_at',
            'updated_at',
            'trades'
        ]
    ]);

    expect($response['data']['trades'])->toBeArray()
        ->and($response['data']['trades'][0]['total_price'])->toBe(1_000_000); // Toman
});

it('fails to sell without gold', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->postJson(route('orders.store'), [
        'type' => 'sell',
        'weight' => 1000,
        'price_per_gram' => 10_000_000,
    ]);

    $response->assertStatus(422);

    $response->assertJson([
        'message' => 'Insufficient available gold.',
        'error'   => 'insufficient_gold',
    ]);
});

it('prevents placing second buy order when balance is fully reserved', function () {
    $user = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'asset' => AssetType::Rial,
        'amount' => 10_000_000,
        'direction' => Direction::Credit,
    ]);

    actingAs($user)->postJson(route('orders.store'), [
        'type' => 'buy',
        'weight' => 1000,
        'price_per_gram' => 10_000_000,
    ])->assertCreated();

    actingAs($user)->postJson(route('orders.store'), [
        'type' => 'buy',
        'weight' => 500,
        'price_per_gram' => 10_000_000,
    ])->assertStatus(422);
});

it('cancels an open order', function () {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Open,
    ]);

    $response = actingAs($user)->deleteJson(route('orders.destroy', $order->uuid));

    $response->assertOk();
    expect($response['data']['status'])->toBe(OrderStatus::Cancelled->value);
});

it('handles partial fills with multiple sellers', function () {
    $buyer = User::factory()->create();
    $seller1 = User::factory()->create();
    $seller2 = User::factory()->create();

    Transaction::factory()->create([
        'user_id' => $buyer->id,
        'asset' => AssetType::Rial,
        'amount' => 20_000_000 * 10,
        'direction' => Direction::Credit,
    ]);

    foreach ([$seller1, $seller2] as $seller) {
        Transaction::factory()->create([
            'user_id' => $seller->id,
            'asset' => AssetType::Gold,
            'amount' => 500,
            'direction' => Direction::Credit,
        ]);

        Order::factory()->create([
            'user_id' => $seller->id,
            'type' => 'sell',
            'price_per_gram' => 10_000_000,
            'total_weight' => 500,
            'remaining_weight' => 500,
            'status' => OrderStatus::Open,
            'total_price' => intval(10_000_000 * 0.5),
        ]);
    }

    $response = actingAs($buyer)->postJson(route('orders.store'), [
        'type' => 'buy',
        'weight' => 1000,
        'price_per_gram' => 10_000_000,
    ]);

    $response->assertCreated();
    expect(count($response['data']['trades']))->toBe(2);
});
