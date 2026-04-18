<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class SaleLine
{
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $saleId,
        private Uuid $orderLineId,
        private Uuid $userId,
        private Quantity $quantity,
        private int $price,
        private int $taxPercentage,
        private int $lineSubtotal,
        private int $taxAmount,
        private ?string $discountType,
        private int $discountValue,
        private int $discountAmount,
        private int $lineTotal,
        private Quantity $refundedQuantity,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        int $restaurantId,
        Uuid $saleId,
        Uuid $orderLineId,
        Uuid $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
        int $lineSubtotal,
        int $taxAmount,
        ?string $discountType,
        int $discountValue,
        int $discountAmount,
        int $lineTotal,
    ): self {
        return new self($uuid, RestaurantId::create($restaurantId), $saleId, $orderLineId, $userId, Quantity::create($quantity), $price, $taxPercentage, $lineSubtotal, $taxAmount, $discountType, $discountValue, $discountAmount, $lineTotal, Quantity::create(0));
    }

    public static function fromPersistence(
        string $uuid,
        int $restaurantId,
        string $saleId,
        string $orderLineId,
        string $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
        int $lineSubtotal,
        int $taxAmount,
        ?string $discountType,
        int $discountValue,
        int $discountAmount,
        int $lineTotal,
        int $refundedQuantity,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            Uuid::create($saleId),
            Uuid::create($orderLineId),
            Uuid::create($userId),
            Quantity::create($quantity),
            $price,
            $taxPercentage,
            $lineSubtotal,
            $taxAmount,
            $discountType,
            $discountValue,
            $discountAmount,
            $lineTotal,
            Quantity::create($refundedQuantity),
        );
    }

    public function registerRefund(int $quantity): void
    {
        $this->refundedQuantity = $this->refundedQuantity->add(Quantity::create($quantity));
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function saleId(): Uuid { return $this->saleId; }
    public function orderLineId(): Uuid { return $this->orderLineId; }
    public function userId(): Uuid { return $this->userId; }
    public function quantity(): int { return $this->quantity->getValue(); }
    public function price(): int { return $this->price; }
    public function taxPercentage(): int { return $this->taxPercentage; }
    public function lineSubtotal(): int { return $this->lineSubtotal; }
    public function taxAmount(): int { return $this->taxAmount; }
    public function discountType(): ?string { return $this->discountType; }
    public function discountValue(): int { return $this->discountValue; }
    public function discountAmount(): int { return $this->discountAmount; }
    public function lineTotal(): int { return $this->lineTotal; }
    public function refundedQuantity(): int { return $this->refundedQuantity->getValue(); }
    public function availableQuantity(): int { return $this->quantity->subtract($this->refundedQuantity)->getValue(); }
}