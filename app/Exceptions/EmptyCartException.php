<?php

namespace App\Exceptions;

use Exception;

class EmptyCartException extends Exception
{
    public function __construct()
    {
        parent::__construct('Your cart is empty.');
    }
}