<?php

namespace App\Services\Money;

interface MoneyPresentationStrategy
{
    public function format(string $whole, string $fraction): string;
}