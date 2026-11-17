<?php

namespace App\Services\Money;

use InvalidArgumentException;

class PhpMoneyFormatter
{
    public function __construct(
        private readonly MoneyPresentationStrategy $strategy
    ) {}

    public function format(int $centavos): string
    {
        if ($centavos < 0) {
            throw new InvalidArgumentException('Minor units must be a nonnegative integer.');
        }

        $wholeValue = intdiv($centavos, 100);
        $fractionValue = $centavos % 100;

        $whole = number_format($wholeValue, 0, '', ',');
        $fraction = str_pad((string) $fractionValue, 2, '0', STR_PAD_LEFT);

        return $this->strategy->format($whole, $fraction);
    }
}