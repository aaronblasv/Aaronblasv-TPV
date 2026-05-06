<?php

declare(strict_types=1);

namespace App\Order\Application\AddOrderLine;

use App\Order\Domain\Entity\OrderLine;

final readonly class AddOrderLineResponse
{
    private function __construct(
        public string $uuid,
        public string $productId,
        public string $userId,
        public int $quantity,
        public int $price,
        public float $taxPercentage,
        public ?string $discountType,
        public int $discountValue,
        public int $discountAmount,
    ) {}

    public static function create(OrderLine $line): self
    {
        return new self(
            $line->uuid()->getValue(),
            $line->productId()->getValue(),
            $line->userId()->getValue(),
            $line->quantity()->getValue(),
            $line->price(),
            $line->taxPercentageAsPercentage(),
            $line->discountType(),
            $line->discountValue(),
            $line->discountAmount(),
        );
    }

    /**
     * @return array<string, string|int|float|null>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'tax_percentage' => $this->taxPercentage,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'discount_amount' => $this->discountAmount,
        ];
    }
}