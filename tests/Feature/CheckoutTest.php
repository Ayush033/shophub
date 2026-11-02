<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

it('places an order, takes payment, lowers stock and empties the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->stock(5)->create(['price_cents' => 2500]);
    addToCart($user, $product, 2);

    $this->actingAs($user)
        ->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()])
        ->assertSuccessful()
        ->assertJsonPath('data.total', 50);

    expect(Order::count())->toBe(1)
        ->and(Payment::count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(3)
        ->and($user->cart->items()->count())->toBe(0);
});

it('refuses to check out an empty cart', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()])
        ->assertUnprocessable();

    expect(Order::count())->toBe(0);
});

it('requires a full shipping address', function () {
    $user = User::factory()->create();
    addToCart($user, Product::factory()->create());

    $this->actingAs($user)
        ->postJson('/api/v1/orders', ['shipping_address' => ['line1' => 'Somewhere']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['shipping_address.city', 'shipping_address.country', 'shipping_address.postal_code']);
});

it('refuses checkout if stock dropped after the item was added to the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->stock(5)->create();
    addToCart($user, $product, 3);

    $product->update(['stock' => 1]);

    $this->actingAs($user)
        ->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()])
        ->assertUnprocessable();

    expect(Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(1);
});

it('only lets one of two buyers get the last item', function () {
    $product = Product::factory()->stock(1)->create();
    $first = User::factory()->create();
    $second = User::factory()->create();
    addToCart($first, $product);
    addToCart($second, $product);

    $this->actingAs($first)
        ->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()])
        ->assertSuccessful();

    $this->actingAs($second)
        ->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()])
        ->assertUnprocessable();

    expect(Order::count())->toBe(1)
        ->and($product->fresh()->stock)->toBe(0);
});

it('rolls back the whole order if the last item is sold mid-checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->stock(1)->create();
    addToCart($user, $product);

    // Another customer buys the last one after our stock check passed,
    // but before our order is saved.
    whenCheckoutTransactionStarts(fn () => Product::whereKey($product->id)->update(['stock' => 0]));

    $this->actingAs($user)
        ->postJson('/api/v1/orders', ['shipping_address' => shippingAddress()])
        ->assertUnprocessable();

    // Nothing from the failed checkout is kept: no order, no payment,
    // and the customer's cart is left as it was.
    expect(Order::count())->toBe(0)
        ->and(Payment::count())->toBe(0)
        ->and($user->cart->items()->count())->toBe(1);
});
