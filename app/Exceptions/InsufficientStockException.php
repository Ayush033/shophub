<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $productName, int $available)
    {
        parent::__construct("Not enough stock for \"{$productName}\". Only {$available} left.");
    }
}