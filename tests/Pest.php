<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * A valid shipping address for checkout requests.
 */
function shippingAddress(): array
{
    return [
        'line1' => '123 Test Street',
        'city' => 'Kathmandu',
        'country' => 'Nepal',
        'postal_code' => '44600',
    ];
}

/**
 * Put a product straight into a user's cart.
 */
function addToCart(User $user, Product $product, int $quantity = 1): void
{
    $cart = $user->cart()->firstOrCreate([]);
    $cart->items()->create(['product_id' => $product->id, 'quantity' => $quantity]);
}

/**
 * Run a callback the moment checkout opens its database transaction.
 *
 * This copies what happens when another customer's request changes the data
 * after our pre-checks have passed but before our order is saved. The change
 * runs inside the checkout transaction, so it is rolled back with it; tests
 * should check what the checkout kept (orders, payments, cart), not this change.
 */
function whenCheckoutTransactionStarts(callable $callback): void
{
    $ran = false;

    Event::listen(TransactionBeginning::class, function () use ($callback, &$ran) {
        if ($ran) {
            return;
        }
        $ran = true;
        $callback();
    });
}
