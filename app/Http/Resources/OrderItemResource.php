<?php

// app/Http/Resources/OrderItemResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,     // the snapshot, not a live join
            'unit_price' => round($this->unit_price_cents / 100, 2),
            'quantity' => $this->quantity,
            'line_total' => round($this->unit_price_cents * $this->quantity / 100, 2),
        ];
    }
}