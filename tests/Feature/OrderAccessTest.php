<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function placeOrderFor(User $user): Order
{
    addToCart($user, Product::factory()->create());
    test()->actingAs($user)->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()]);

    return $user->orders()->latest('id')->first();
}

it('lets a customer view their own order', function () {
    $user = User::factory()->create();
    $order = placeOrderFor($user);

    $this->actingAs($user)->getJson("/api/v1/orders/{$order->id}")->assertOk();
});

it("blocks a customer from viewing someone else's order", function () {
    $order = placeOrderFor(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->getJson("/api/v1/orders/{$order->id}")
        ->assertForbidden();
});

it('only lists the customer\'s own orders', function () {
    $user = User::factory()->create();
    placeOrderFor($user);
    placeOrderFor(User::factory()->create());

    $this->actingAs($user)->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lets an admin see every order', function () {
    placeOrderFor(User::factory()->create());
    placeOrderFor(User::factory()->create());

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('blocks customers from changing order status', function () {
    $user = User::factory()->create();
    $order = placeOrderFor($user);

    $this->actingAs($user)
        ->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'delivered'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('paid');
});

it('lets an admin change order status', function () {
    $order = placeOrderFor(User::factory()->create());

    $this->actingAs(User::factory()->admin()->create())
        ->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
        ->assertOk();

    expect($order->fresh()->status)->toBe('shipped');
});

it('blocks customers from admin-only routes', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/coupons', ['code' => 'FREE', 'discount_type' => 'fixed', 'discount_value' => 100])
        ->assertForbidden();
});
