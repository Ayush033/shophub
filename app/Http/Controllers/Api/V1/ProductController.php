<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->active()
            ->with('category')
            ->withCount('reviews')
            ->inCategory($request->integer('category_id') ?: null)
            ->priceBetween(
                $request->filled('min_price') ? (int) round($request->float('min_price') * 100) : null,
                $request->filled('max_price') ? (int) round($request->float('max_price') * 100) : null,
            )
            ->search($request->string('q')->toString() ?: null)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['category', 'reviews.user']));
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $product = Product::create([
            ...$request->safe()->except(['price', 'slug']),
            'slug' => $request->validated('slug'),
            'price_cents' => $request->priceInCents(),
        ]);

        return new ProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->safe()->except('price');

        if ($request->priceInCents() !== null) {
            $data['price_cents'] = $request->priceInCents();
        }

        $product->update($data);

        return new ProductResource($product->fresh('category'));
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete(); // soft delete

        return response()->json(['message' => 'Product deleted.']);
    }
}