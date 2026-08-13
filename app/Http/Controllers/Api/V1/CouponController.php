<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyCouponRequest;
use App\Http\Requests\StoreCouponRequest;
use App\Models\Coupon;
use App\Services\CouponService;

class CouponController extends Controller
{
    public function __construct(private CouponService $couponService) {}

    public function store(StoreCouponRequest $request)
    {
        $coupon = Coupon::create($request->validated());

        return response()->json($coupon, 201);
    }

    public function preview(ApplyCouponRequest $request)
    {
        $coupon = $this->couponService->findValid($request->validated('code'));

        return response()->json([
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'valid' => true,
        ]);
    }
}