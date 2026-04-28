<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Shared\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class ServiceWindowLine
{
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $orderLineId,
        private string $productName,
        private Quantity $quantity,
        private int $price,
        private int $taxPercentage,
        private ?string $discountType,
        private int $discountValue,
        private int $discountAmount,
        private int $lineSubtotal,
        private int $taxAmount,
        private int $lineTotal,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        int $restaurantId,
        Uuid $orderLineId,
        string $productName,
        int $quantity,
        int $price,
        int $taxPercentage,
        ?string $discountType,
        int $discountValue,
        int $discountAmount,
        int $lineSubtotal,
        int $taxAmount,
        int $lineTotal,
    ): self {
        return new self(
            $uuid,
            RestaurantId::create($restaurantId),
            $orderLineId,
            $productName,
            Quantity::create($quantity),
            $price,
            $taxPercentage,
            $discountType,
            $discountValue,
            $discountAmount,
            $lineSubtotal,
            $taxAmount,
            $lineTotal,
        );
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function restaurantId(): int
    {
        return $this->restaurantId->getValue();
    }

    public function orderLineId(): Uuid
    {
        return $this->orderLineId;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function quantity(): int
    {
        return $this->quantity->getValue();
    }

    public function price(): int
    {
        return $this->price;
    }

    public function taxPercentage(): int
    {
        return $this->taxPercentage;
    }

    public function discountType(): ?string
    {
        return $this->discountType;
    }

    public function discountValue(): int
    {
        return $this->discountValue;
    }

    public function discountAmount(): int
    {
        return $this->discountAmount;
    }

    public function lineSubtotal(): int
    {
        return $this->lineSubtotal;
    }

    public function taxAmount(): int
    {
        return $this->taxAmount;
    }

    public function lineTotal(): int
    {
        return $this->lineTotal;
    }
}
