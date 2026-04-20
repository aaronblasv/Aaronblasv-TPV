<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class OrderLine
{
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $orderId,
        private Uuid $productId,
        private Uuid $userId,
        private Quantity $quantity,
        private int $price,
        private int $taxPercentage,
        private ?string $discountType,
        private int $discountValue,
        private int $discountAmount,
    ) {}

    public static function fromPersistence(
        string $uuid,
        int $restaurantId,
        string $orderId,
        string $productId,
        string $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
        ?string $discountType,
        int $discountValue,
        int $discountAmount,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            Uuid::create($orderId),
            Uuid::create($productId),
            Uuid::create($userId),
            Quantity::create($quantity),
            $price,
            $taxPercentage,
            $discountType,
            $discountValue,
            $discountAmount,
        );
    }

    public static function dddCreate(
        Uuid $uuid,
        int $restaurantId,
        Uuid $orderId,
        Uuid $productId,
        Uuid $userId,
        Quantity $quantity,
        int $price,
        int $taxPercentage,
        ?string $discountType = null,
        int $discountValue = 0,
        int $discountAmount = 0,
    ): self {
        return new self($uuid, RestaurantId::create($restaurantId), $orderId, $productId, $userId, $quantity, $price, $taxPercentage, $discountType, $discountValue, $discountAmount);
    }

    public function updateQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
        $this->recalculateDiscountAmount();
    }

    public function applyDiscount(?string $discountType, int $discountValue): void
    {
        if ($discountType === null || $discountValue <= 0) {
            $this->discountType = null;
            $this->discountValue = 0;
            $this->discountAmount = 0;

            return;
        }

        $this->discountType = $discountType;
        $this->discountValue = $discountValue;
        $this->recalculateDiscountAmount();
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function orderId(): Uuid { return $this->orderId; }
    public function productId(): Uuid { return $this->productId; }
    public function userId(): Uuid { return $this->userId; }
    public function quantity(): Quantity { return $this->quantity; }
    public function price(): int { return $this->price; }
    public function taxPercentage(): int { return $this->taxPercentage; }
    public function discountType(): ?string { return $this->discountType; }
    public function discountValue(): int { return $this->discountValue; }
    public function discountAmount(): int { return $this->discountAmount; }
    public function subtotal(): int { return $this->price * $this->quantity->getValue(); }
    public function subtotalAfterDiscount(): int { return max(0, $this->subtotal() - $this->discountAmount); }
    public function taxAmount(): int { return (int) round($this->subtotalAfterDiscount() * $this->taxPercentage / 100); }
    public function total(): int { return $this->subtotalAfterDiscount() + $this->taxAmount(); }

    private function recalculateDiscountAmount(): void
    {
        if ($this->discountType === null || $this->discountValue <= 0) {
            $this->discountAmount = 0;

            return;
        }

        $baseAmount = $this->subtotal();
        $rawAmount = $this->discountType === 'percentage'
            ? (int) round($baseAmount * $this->discountValue / 100)
            : $this->discountValue;

        $this->discountAmount = max(0, min($baseAmount, $rawAmount));
    }
}