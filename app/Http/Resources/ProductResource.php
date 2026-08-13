<?php

// app/Http/Resources/ProductResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,          // accessor from the model, not price_cents
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'is_active' => $this->is_active,
            'images' => $this->images ?? [],
            'category' => new CategoryResource($this->whenLoaded('category')),
            'average_rating' => $this->when(
                $this->relationLoaded('reviews'),
                fn () => $this->averageRating()
            ),
            'reviews_count' => $this->whenCounted('reviews'),
            'created_at' => $this->created_at,
        ];
    }
}
