<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\AmountDiscount;
use App\Order\Domain\ValueObject\DiscountPolicy;
use App\Order\Domain\ValueObject\DiscountType;
use App\Order\Domain\ValueObject\NoDiscount;
use App\Order\Domain\ValueObject\PercentageDiscount;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Interfaces\HasDomainEventsInterface;
use App\Shared\Domain\Support\RecordsDomainEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class OrderLine implements HasDomainEventsInterface
{
    use RecordsDomainEvents;

    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $orderId,
        private Uuid $productId,
        private Uuid $userId,
        private Quantity $quantity,
        private Money $price,
        private int $taxPercentage,
        private ?DiscountType $discountType,
        private int $discountValue,
        private Money $discountAmount,
        private ?DomainDateTime $sentToKitchenAt,
        private ?DomainDateTime $paidAt,
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
        ?\DateTimeImmutable $sentToKitchenAt,
        ?\DateTimeImmutable $paidAt,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            Uuid::create($orderId),
            Uuid::create($productId),
            Uuid::create($userId),
            Quantity::create($quantity),
            Money::create($price),
            $taxPercentage,
            DiscountType::create($discountType),
            $discountValue,
            Money::create($discountAmount),
            $sentToKitchenAt ? DomainDateTime::create($sentToKitchenAt) : null,
            $paidAt ? DomainDateTime::create($paidAt) : null,
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
        ?DomainDateTime $sentToKitchenAt = null,
        ?DomainDateTime $paidAt = null,
    ): self {
        $line = new self(
            $uuid,
            RestaurantId::create($restaurantId),
            $orderId,
            $productId,
            $userId,
            $quantity,
            Money::create($price),
            $taxPercentage,
            DiscountType::create($discountType),
            $discountValue,
            Money::create($discountAmount),
            $sentToKitchenAt,
            $paidAt,
        );

        $line->recalculateDiscountAmount();

        return $line;
    }

    public function moveToOrder(Uuid $orderId): void
    {
        $this->orderId = $orderId;
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
            $this->discountAmount = Money::zero();

            return;
        }

        $this->discountType = DiscountType::create($discountType);
        $this->discountValue = $discountValue;
        $this->recalculateDiscountAmount();
    }

    public function markSentToKitchen(): void
    {
        if ($this->sentToKitchenAt !== null) {
            return;
        }

        $this->sentToKitchenAt = DomainDateTime::now();
    }

    public function markPaid(): void
    {
        if ($this->paidAt !== null) {
            return;
        }

        $this->paidAt = DomainDateTime::now();
    }

    public function splitOffPaidQuantity(int $paidQuantity, Uuid $paidLineUuid): array
    {
        $currentQuantity = $this->quantity->getValue();

        if ($paidQuantity <= 0 || $paidQuantity > $currentQuantity) {
            throw new \InvalidArgumentException('Invalid paid quantity for order line split.');
        }

        if ($paidQuantity === $currentQuantity) {
            $this->markPaid();

            return [$this, null];
        }

        $remainingQuantity = $currentQuantity - $paidQuantity;
        [$paidDiscountValue, $remainingDiscountValue] = $this->splitDiscountValue($paidQuantity, $currentQuantity);

        $paidLine = self::dddCreate(
            $paidLineUuid,
            $this->restaurantId(),
            $this->orderId,
            $this->productId,
            $this->userId,
            Quantity::create($paidQuantity),
            $this->price(),
            $this->taxPercentage,
            $this->discountType(),
            $paidDiscountValue,
            0,
            $this->sentToKitchenAt,
            DomainDateTime::now(),
        );

        $this->quantity = Quantity::create($remainingQuantity);
        $this->discountValue = $remainingDiscountValue;
        $this->recalculateDiscountAmount();

        return [$paidLine, $this];
    }

    public function id(): Uuid
    {
        return $this->uuid;
    }

    public function uuid(): Uuid
    {
        return $this->id();
    }

    public function restaurantId(): int
    {
        return $this->restaurantId->getValue();
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function price(): int
    {
        return $this->price->getValue();
    }

    public function taxPercentage(): int
    {
        return $this->taxPercentage;
    }

    public function discountType(): ?string
    {
        return $this->discountType?->value;
    }

    public function discountValue(): int
    {
        return $this->discountValue;
    }

    public function discountAmount(): int
    {
        return $this->discountAmount->getValue();
    }

    public function sentToKitchenAt(): ?DomainDateTime
    {
        return $this->sentToKitchenAt;
    }

    public function paidAt(): ?DomainDateTime
    {
        return $this->paidAt;
    }

    public function isSentToKitchen(): bool
    {
        return $this->sentToKitchenAt !== null;
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null;
    }

    public function subtotal(): int
    {
        return $this->price->getValue() * $this->quantity->getValue();
    }

    public function subtotalAfterDiscount(): int
    {
        return max(0, $this->subtotal() - $this->discountAmount->getValue());
    }

    public function taxAmount(): int
    {
        return (int) round($this->subtotalAfterDiscount() * $this->taxPercentage / 100);
    }

    public function total(): int
    {
        return $this->subtotalAfterDiscount() + $this->taxAmount();
    }

    private function recalculateDiscountAmount(): void
    {
        $this->discountAmount = $this->discountPolicy()->applyTo(Money::fromCents($this->subtotal()));
    }

    private function discountPolicy(): DiscountPolicy
    {
        if ($this->discountType === null || $this->discountValue <= 0) {
            return new NoDiscount;
        }

        return match ($this->discountType) {
            DiscountType::AMOUNT => new AmountDiscount($this->discountValue),
            DiscountType::PERCENTAGE => new PercentageDiscount($this->discountValue),
        };
    }

    private function splitDiscountValue(int $paidQuantity, int $currentQuantity): array
    {
        if ($this->discountType !== DiscountType::AMOUNT || $this->discountValue <= 0) {
            return [$this->discountValue, $this->discountValue];
        }

        $paidDiscountValue = (int) floor($this->discountValue * $paidQuantity / $currentQuantity);
        $remainingDiscountValue = $this->discountValue - $paidDiscountValue;

        return [$paidDiscountValue, $remainingDiscountValue];
    }
}
