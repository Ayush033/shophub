<?php

namespace App\Exceptions;

use Exception;

class InvalidCouponException extends Exception
{
    public function __construct(string $reason = 'This coupon is not valid.')
    {
        parent::__construct($reason);
    }
}