<?php

namespace App\Services\Money;

class CodeStrategy implements MoneyPresentationStrategy
{
    public function format(string $whole, string $fraction): string
    {
        return "PHP {$whole}.{$fraction}";
    }
}