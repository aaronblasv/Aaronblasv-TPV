<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Order\Domain\ValueObject\Percentage;

final class Discount
{
    private function __construct() {}

    public static function calculateAmount(string $discountType, int $value, int $baseAmount): int
    {
        $rawAmount = match ($discountType) {
            'percentage' => Percentage::create($value)->applyTo($baseAmount),
            'amount' => $value,
            default => throw new \InvalidArgumentException("Invalid discount type: {$discountType}"),
        };

        return max(0, min($baseAmount, $rawAmount));
    }
}