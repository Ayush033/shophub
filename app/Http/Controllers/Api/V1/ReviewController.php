<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Product $product)
    {
        return ReviewResource::collection(
            $product->reviews()->with('user')->latest()->paginate(10)
        );
    }

    public function store(StoreReviewRequest $request, Product $product)
    {
        $user = $request->user();

        if ($product->reviews()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'product' => ['You have already reviewed this product.'],
            ]);
        }

        $isVerifiedPurchase = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('payment_status', 'paid'))
            ->exists();

        $review = $product->reviews()->create([
            ...$request->validated(),
            'user_id' => $user->id,
            'is_verified_purchase' => $isVerifiedPurchase,
        ]);

        return new ReviewResource($review->load('user'));
    }

    public function destroy(Request $request, Review $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}