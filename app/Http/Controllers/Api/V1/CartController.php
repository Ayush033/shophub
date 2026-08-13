<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function show(Request $request)
    {
        $cart = $this->cartService->cartFor($request->user())->load('items.product');

        return new CartResource($cart);
    }

    public function addItem(AddCartItemRequest $request)
    {
        $product = Product::findOrFail($request->validated('product_id'));

        $cart = $this->cartService->addItem($request->user(), $product, $request->validated('quantity'));

        return new CartResource($cart);
    }

    public function updateItem(UpdateCartItemRequest $request, int $cartItem)
    {
        $cart = $this->cartService->updateItemQuantity($request->user(), $cartItem, $request->validated('quantity'));

        return new CartResource($cart);
    }

    public function removeItem(Request $request, int $cartItem)
    {
        $cart = $this->cartService->removeItem($request->user(), $cartItem);

        return new CartResource($cart);
    }
}