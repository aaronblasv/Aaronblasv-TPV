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
        public int $taxPercentage,
    ) {}

    public static function create(OrderLine $line): self
    {
        return new self(
            $line->uuid()->getValue(),
            $line->productId()->getValue(),
            $line->userId()->getValue(),
            $line->quantity()->getValue(),
            $line->price(),
            $line->taxPercentage(),
        );
    }

    /**
     * @return array<string, string|int>
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
        ];
    }
}