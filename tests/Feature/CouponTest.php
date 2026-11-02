<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->create();
    $this->product = Product::factory()->stock(10)->create(['price_cents' => 10000]);
    addToCart($this->user, $this->product);
});

function checkoutWith(string $code)
{
    return test()->actingAs(test()->user)->postJson('/api/v1/orders', [
        'shipping_address' => shippingAddress(),
        'coupon_code' => $code,
    ]);
}

it('applies a percentage discount', function () {
    $coupon = Coupon::factory()->create(['discount_type' => 'percentage', 'discount_value' => 20]);

    checkoutWith($coupon->code)
        ->assertSuccessful()
        ->assertJsonPath('data.discount', 20)
        ->assertJsonPath('data.total', 80);

    expect($coupon->fresh()->times_used)->toBe(1);
});

it('never discounts more than the order is worth', function () {
    $coupon = Coupon::factory()->create(['discount_type' => 'fixed', 'discount_value' => 50000]);

    checkoutWith($coupon->code)
        ->assertSuccessful()
        ->assertJsonPath('data.total', 0);
});

it('rejects an expired coupon', function () {
    $coupon = Coupon::factory()->expired()->create();

    checkoutWith($coupon->code)->assertUnprocessable();

    expect(Order::count())->toBe(0);
});

it('rejects an inactive coupon', function () {
    $coupon = Coupon::factory()->create(['is_active' => false]);

    checkoutWith($coupon->code)->assertUnprocessable();
});

it('rejects a coupon that has reached its usage limit', function () {
    $coupon = Coupon::factory()->create(['usage_limit' => 1, 'times_used' => 1]);

    checkoutWith($coupon->code)->assertUnprocessable();

    expect(Order::count())->toBe(0);
});

it('does not go over the usage limit when the last use is taken mid-checkout', function () {
    $coupon = Coupon::factory()->create(['usage_limit' => 1, 'times_used' => 0]);

    // Someone else uses the last redemption after our coupon check passed.
    whenCheckoutTransactionStarts(fn () => Coupon::whereKey($coupon->id)->update(['times_used' => 1]));

    checkoutWith($coupon->code)->assertUnprocessable();

    // The checkout is refused and fully rolled back: no order, stock untouched.
    expect(Order::count())->toBe(0)
        ->and($this->product->fresh()->stock)->toBe(10);
});
