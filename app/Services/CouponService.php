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

    public function redeem(Coupon $coupon): void
    {
        $coupon->increment('times_used');
    }
}