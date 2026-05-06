<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ReadModel;

final readonly class OrderLineForInvoice
{
    public function __construct(
        public string $uuid,
        public int $quantity,
        public int $price,
        public int $taxPercentage,
        public ?string $discountType,
        public int $discountValue,
        public int $discountAmount,
    ) {}

    public function subtotalAfterDiscount(): int
    {
        return ($this->quantity * $this->price) - $this->discountAmount;
    }

    public function taxAmount(): int
    {
        return (int) round($this->subtotalAfterDiscount() * $this->taxPercentage / 10000);
    }
}
