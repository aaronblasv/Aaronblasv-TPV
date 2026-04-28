<?php

declare(strict_types=1);

namespace App\Sale\Domain\ReadModel;

final readonly class SaleServiceWindowLine
{
    public function __construct(
        public string $uuid,
        public string $productName,
        public int $quantity,
        public int $price,
        public int $taxPercentage,
        public ?string $discountType,
        public int $discountValue,
        public int $discountAmount,
        public int $lineSubtotal,
        public int $taxAmount,
        public int $lineTotal,
    ) {}
}
