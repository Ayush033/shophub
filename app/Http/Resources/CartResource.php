<?php

// app/Http/Resources/CartResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'subtotal' => round($this->subtotalCents() / 100, 2),
            'item_count' => $this->items->sum('quantity'),
        ];
    }
}
