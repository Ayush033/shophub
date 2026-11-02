<?php

namespace App\Services;

use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;

class CouponService
{
    public function findValid(string $code): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            throw new InvalidCouponException('Coupon code not found.');
        }

        if (! $coupon->isValid()) {
            throw new InvalidCouponException('This coupon has expired or is no longer available.');
        }

        return $coupon;
    }

    /**
     * Count one use of the coupon.
     *
     * The usage limit is checked inside the same UPDATE as the increment, so two
     * checkouts running at the same time can't push a coupon past its limit.
     * If the limit was already reached, this throws and the checkout rolls back.
     */
    public function redeem(Coupon $coupon): void
    {
        $affected = Coupon::where('id', $coupon->id)
            ->where(fn ($query) => $query
                ->whereNull('usage_limit')
                ->orWhereColumn('times_used', '<', 'usage_limit'))
            ->increment('times_used');

        if (! $affected) {
            throw new InvalidCouponException('This coupon has reached its usage limit.');
        }
    }
}