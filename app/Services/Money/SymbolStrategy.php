<?php

namespace App\Services\Money;

class SymbolStrategy implements MoneyPresentationStrategy
{
    public function format(string $whole, string $fraction): string
    {
        return "₱{$whole}.{$fraction}";
    }
}