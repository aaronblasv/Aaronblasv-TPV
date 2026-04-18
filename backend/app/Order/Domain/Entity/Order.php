<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\Diners;

class Order
{
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private OrderStatus $status,
        private Uuid $tableId,
        private Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private Diners $diners,
        private ?string $discountType,
        private int $discountValue,
        private int $discountAmount,
        private DomainDateTime $openedAt,
        private ?DomainDateTime $closedAt,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        int $restaurantId,
        Uuid $tableId,
        Uuid $openedByUserId,
        Diners $diners,
    ): self {
        return new self(
            $uuid,
            RestaurantId::create($restaurantId),
            OrderStatus::open(),
            $tableId,
            $openedByUserId,
            null,
            $diners,
            null,
            0,
            0,
            DomainDateTime::now(),
            null,
        );
    }

    public static function fromPersistence(
        string $uuid,
        int $restaurantId,
        string $status,
        string $tableId,
        string $openedByUserId,
        ?string $closedByUserId,
        int $diners,
        ?string $discountType,
        int $discountValue,
        int $discountAmount,
        \DateTimeImmutable $openedAt,
        ?\DateTimeImmutable $closedAt,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            OrderStatus::create($status),
            Uuid::create($tableId),
            Uuid::create($openedByUserId),
            $closedByUserId ? Uuid::create($closedByUserId) : null,
            Diners::create($diners),
            $discountType,
            $discountValue,
            $discountAmount,
            DomainDateTime::create($openedAt),
            $closedAt ? DomainDateTime::create($closedAt) : null,
        );
    }

    public function close(Uuid $closedByUserId): void
    {
        if (!$this->status->isOpen()) {
            throw new \DomainException('Cannot close an order that is not open.');
        }
        $this->status = OrderStatus::closed();
        $this->closedByUserId = $closedByUserId;
        $this->closedAt = DomainDateTime::now();
    }

    public function updateDiners(Diners $diners): void
    {
        if (!$this->status->isOpen()) {
            throw new \App\Order\Domain\Exception\CannotUpdateDinersOnClosedOrderException($this->uuid->getValue());
        }
        $this->diners = $diners;
    }

    public function applyDiscount(?string $discountType, int $discountValue, int $baseAmount): void
    {
        if ($discountType === null || $discountValue <= 0) {
            $this->discountType = null;
            $this->discountValue = 0;
            $this->discountAmount = 0;

            return;
        }

        $this->discountType = $discountType;
        $this->discountValue = $discountValue;
        $this->discountAmount = self::calculateDiscountAmount($discountType, $discountValue, $baseAmount);
    }

    public function cancel(): void
    {
        if (!$this->status->isOpen()) {
            throw new \DomainException('Only open orders can be cancelled.');
        }
        $this->status = OrderStatus::cancelled();
    }

    public function markAsInvoiced(): void
    {
        if (!$this->status->isClosed()) {
            throw new \DomainException('Only closed orders can be marked as invoiced.');
        }
        $this->status = OrderStatus::invoiced();
    }

    public function moveToTable(Uuid $tableId): void
    {
        if (!$this->status->isOpen()) {
            throw new \DomainException('Cannot move an order that is not open.');
        }

        $this->tableId = $tableId;
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function status(): OrderStatus { return $this->status; }
    public function tableId(): Uuid { return $this->tableId; }
    public function openedByUserId(): Uuid { return $this->openedByUserId; }
    public function closedByUserId(): ?Uuid { return $this->closedByUserId; }
    public function diners(): Diners { return $this->diners; }
    public function discountType(): ?string { return $this->discountType; }
    public function discountValue(): int { return $this->discountValue; }
    public function discountAmount(): int { return $this->discountAmount; }
    public function openedAt(): DomainDateTime { return $this->openedAt; }
    public function closedAt(): ?DomainDateTime { return $this->closedAt; }

    public function calculateSubtotal(array $lines): int
    {
        return max(0, $this->calculateLinesSubtotalAfterDiscounts($lines) - $this->calculateOrderDiscountAmount($lines));
    }

    public function calculateTaxAmount(array $lines): int
    {
        $lineSubtotal = $this->calculateLinesSubtotalAfterDiscounts($lines);
        if ($lineSubtotal <= 0) {
            return 0;
        }

        $taxBeforeOrderDiscount = array_reduce($lines, function ($carry, $line) {
            return $carry + $line->taxAmount();
        }, 0);

        $ratio = max(0, ($lineSubtotal - $this->calculateOrderDiscountAmount($lines)) / $lineSubtotal);

        return (int) round($taxBeforeOrderDiscount * $ratio);
    }

    public function calculateLineDiscountTotal(array $lines): int
    {
        return array_reduce($lines, fn ($carry, $line) => $carry + $line->discountAmount(), 0);
    }

    public function calculateOrderDiscountAmount(array $lines): int
    {
        if ($this->discountType === null || $this->discountValue <= 0) {
            return 0;
        }

        return self::calculateDiscountAmount(
            $this->discountType,
            $this->discountValue,
            $this->calculateLinesSubtotalAfterDiscounts($lines),
        );
    }

    private function calculateLinesSubtotalAfterDiscounts(array $lines): int
    {
        return array_reduce($lines, fn ($carry, $line) => $carry + $line->subtotalAfterDiscount(), 0);
    }

    private static function calculateDiscountAmount(string $discountType, int $discountValue, int $baseAmount): int
    {
        $rawAmount = $discountType === 'percentage'
            ? (int) round($baseAmount * $discountValue / 100)
            : $discountValue;

        return max(0, min($baseAmount, $rawAmount));
    }
}