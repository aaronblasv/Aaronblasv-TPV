<?php

declare(strict_types=1);

namespace App\Sale\Domain\ReadModel;

final readonly class SaleLineDetail
{
    public function __construct(
        public string $uuid,
        public string $productName,
        public int $quantity,
        public int $price,
        public int $taxPercentage,
        public int $lineSubtotal,
        public int $taxAmount,
        public ?string $discountType,
        public int $discountValue,
        public int $discountAmount,
        public int $lineTotal,
        public int $refundedQuantity,
    ) {}

    public function taxPercentageAsPercentage(): float
    {
        return $this->taxPercentage / 100;
    }
}
