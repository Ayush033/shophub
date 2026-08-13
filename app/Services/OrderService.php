<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService,
    ) {}

    public function checkout(User $user, array $shippingAddress, ?string $couponCode = null): Order
    {
        $cart = $this->cartService->cartFor($user)->load('items.product');

        if ($cart->items->isEmpty()) {
            throw new EmptyCartException;
        }

        // Re-validate stock right before committing — quantities could have
        // changed since the item was added to the cart.
        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock) {
                throw new InsufficientStockException($item->product->name, $item->product->stock);
            }
        }

        $coupon = $couponCode ? $this->couponService->findValid($couponCode) : null;

        return DB::transaction(function () use ($cart, $coupon, $shippingAddress, $user) {
            $subtotalCents = $cart->subtotalCents();
            $discountCents = $coupon ? $coupon->discountFor($subtotalCents) : 0;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'status' => 'paid',
                'payment_status' => 'paid',
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'total_cents' => $subtotalCents - $discountCents,
                'shipping_address' => $shippingAddress,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'unit_price_cents' => $item->product->price_cents,
                    'quantity' => $item->quantity,
                ]);

                $this->decrementStock($item->product, $item->quantity);
            }

            $order->payment()->create([
                'transaction_reference' => 'MOCK-'.Str::upper(Str::random(12)),
                'amount_cents' => $order->total_cents,
                'status' => 'succeeded',
                'meta' => ['note' => 'Simulated payment — plug in Stripe/PayPal here.'],
            ]);

            if ($coupon) {
                $this->couponService->redeem($coupon);
            }

            $this->cartService->clear($cart);

            OrderPlaced::dispatch($order->load('items'));

            return $order->load(['items', 'payment']);
        });
    }

    private function decrementStock(Product $product, int $quantity): void
    {
        $affected = Product::where('id', $product->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if (! $affected) {
            throw new InsufficientStockException($product->name, $product->fresh()->stock);
        }
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Y').'-'.Str::upper(Str::random(8));
    }
}