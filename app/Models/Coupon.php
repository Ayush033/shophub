<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'discount_type', 'discount_value', 'usage_limit', 'times_used', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) return false;
        return true;
    }

    public function discountFor(int $subtotalCents): int
    {
        $discount = $this->discount_type === 'percentage'
            ? (int) round($subtotalCents * ($this->discount_value / 100))
            : $this->discount_value;

        // never let a discount exceed the subtotal
        return min($discount, $subtotalCents);
    }
}
