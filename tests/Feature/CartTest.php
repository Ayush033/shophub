<?php

use App\Models\Product;
use App\Models\User;

it('adds a product to the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->stock(5)->create();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk();

    expect($user->cart->items()->first()->quantity)->toBe(2);
});

it('adds to the existing quantity instead of creating a duplicate row', function () {
    $user = User::factory()->create();
    $product = Product::factory()->stock(5)->create();

    $this->actingAs($user)->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);
    $this->actingAs($user)->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);

    expect($user->cart->items()->count())->toBe(1)
        ->and($user->cart->items()->first()->quantity)->toBe(3);
});

it('does not let the cart hold more than the stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->stock(2)->create();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3])
        ->assertUnprocessable();
});

it('rejects invalid quantities', function (mixed $quantity) {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => $quantity])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
})->with([0, -1, 101, 'abc']);

it("does not let a user change someone else's cart item", function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    addToCart($owner, Product::factory()->create());
    $itemId = $owner->cart->items()->first()->id;

    $this->actingAs($other)
        ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 5])
        ->assertNotFound();

    expect($owner->cart->items()->first()->quantity)->toBe(1);
});
