<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\Uuid;

class SaleLine
{
    private function __construct(
        private Uuid $uuid,
        private int $restaurantId,
        private Uuid $saleId,
        private Uuid $orderLineId,
        private Uuid $userId,
        private int $quantity,
        private int $price,
        private int $taxPercentage,
        private int $lineSubtotal,
        private int $taxAmount,
        private ?string $discountType,
        private int $discountValue,
        private int $discountAmount,
        private int $lineTotal,
        private int $refundedQuantity,
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
        return new self($uuid, $restaurantId, $saleId, $orderLineId, $userId, $quantity, $price, $taxPercentage, $lineSubtotal, $taxAmount, $discountType, $discountValue, $discountAmount, $lineTotal, 0);
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
            $restaurantId,
            Uuid::create($saleId),
            Uuid::create($orderLineId),
            Uuid::create($userId),
            $quantity,
            $price,
            $taxPercentage,
            $lineSubtotal,
            $taxAmount,
            $discountType,
            $discountValue,
            $discountAmount,
            $lineTotal,
            $refundedQuantity,
        );
    }

    public function registerRefund(int $quantity): void
    {
        $this->refundedQuantity += $quantity;
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): int { return $this->restaurantId; }
    public function saleId(): Uuid { return $this->saleId; }
    public function orderLineId(): Uuid { return $this->orderLineId; }
    public function userId(): Uuid { return $this->userId; }
    public function quantity(): int { return $this->quantity; }
    public function price(): int { return $this->price; }
    public function taxPercentage(): int { return $this->taxPercentage; }
    public function lineSubtotal(): int { return $this->lineSubtotal; }
    public function taxAmount(): int { return $this->taxAmount; }
    public function discountType(): ?string { return $this->discountType; }
    public function discountValue(): int { return $this->discountValue; }
    public function discountAmount(): int { return $this->discountAmount; }
    public function lineTotal(): int { return $this->lineTotal; }
    public function refundedQuantity(): int { return $this->refundedQuantity; }
    public function availableQuantity(): int { return max(0, $this->quantity - $this->refundedQuantity); }
}