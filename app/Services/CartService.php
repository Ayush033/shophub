<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;

class CartService
{
    public function cartFor(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, Product $product, int $quantity): Cart
    {
        $cart = $this->cartFor($user);

        $existing = $cart->items()->where('product_id', $product->id)->first();
        $desiredQuantity = $quantity + ($existing?->quantity ?? 0);

        $this->assertStockAvailable($product, $desiredQuantity);

        $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $desiredQuantity]
        );

        return $cart->fresh('items.product');
    }

    public function updateItemQuantity(User $user, int $cartItemId, int $quantity): Cart
    {
        $cart = $this->cartFor($user);
        $item = $cart->items()->findOrFail($cartItemId);

        $this->assertStockAvailable($item->product, $quantity);
        $item->update(['quantity' => $quantity]);

        return $cart->fresh('items.product');
    }

    public function removeItem(User $user, int $cartItemId): Cart
    {
        $cart = $this->cartFor($user);
        $cart->items()->where('id', $cartItemId)->delete();

        return $cart->fresh('items.product');
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    private function assertStockAvailable(Product $product, int $desiredQuantity): void
    {
        if ($desiredQuantity > $product->stock) {
            throw new InsufficientStockException($product->name, $product->stock);
        }
    }
}