<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\Exception\CannotCancelClosedOrderException;
use App\Order\Domain\Exception\CannotCloseClosedOrderException;
use App\Order\Domain\Exception\CannotInvoiceOpenOrderException;
use App\Order\Domain\Exception\CannotTransferClosedOrderException;
use App\Order\Domain\ValueObject\Diners;
use App\Order\Domain\ValueObject\DiscountType;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Interfaces\HasDomainEventsInterface;
use App\Shared\Domain\Support\RecordsDomainEvents;
use App\Shared\Domain\ValueObject\Discount;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class Order implements HasDomainEventsInterface
{
    use RecordsDomainEvents;

    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private OrderStatus $status,
        private Uuid $tableId,
        private Uuid $openedByUserId,
        private ?Uuid $closedByUserId,
        private Diners $diners,
        private ?DiscountType $discountType,
        private int $discountValue,
        private Money $discountAmount,
        private DomainDateTime $openedAt,
        private ?DomainDateTime $closedAt,
        private ?DomainDateTime $persistedAt,
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
            Money::zero(),
            DomainDateTime::now(),
            null,
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
        ?\DateTimeImmutable $persistedAt,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            OrderStatus::create($status),
            Uuid::create($tableId),
            Uuid::create($openedByUserId),
            $closedByUserId ? Uuid::create($closedByUserId) : null,
            Diners::create($diners),
            DiscountType::create($discountType),
            $discountValue,
            Money::create($discountAmount),
            DomainDateTime::create($openedAt),
            $closedAt ? DomainDateTime::create($closedAt) : null,
            $persistedAt ? DomainDateTime::create($persistedAt) : null,
        );
    }

    public function close(Uuid $closedByUserId): void
    {
        if (!$this->status->isOpen()) {
            throw new CannotCloseClosedOrderException($this->uuid->getValue());
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
            $this->discountAmount = Money::zero();

            return;
        }

        $discountTypeVO = DiscountType::create($discountType);
        $this->discountType = $discountTypeVO;
        $this->discountValue = $discountValue;
        $this->discountAmount = Money::create(Discount::calculateAmount($discountTypeVO->value, $discountValue, $baseAmount));
    }

    public function cancel(): void
    {
        if (!$this->status->isOpen()) {
            throw new CannotCancelClosedOrderException($this->uuid->getValue());
        }
        $this->status = OrderStatus::cancelled();
    }

    public function markAsInvoiced(): void
    {
        if (!$this->status->isClosed()) {
            throw new CannotInvoiceOpenOrderException($this->uuid->getValue());
        }
        $this->status = OrderStatus::invoiced();
    }

    public function moveToTable(Uuid $tableId): void
    {
        if (!$this->status->isOpen()) {
            throw new CannotTransferClosedOrderException($this->uuid->getValue());
        }

        $this->tableId = $tableId;
    }

    public function id(): Uuid { return $this->uuid; }
    public function uuid(): Uuid { return $this->id(); }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function status(): OrderStatus { return $this->status; }
    public function tableId(): Uuid { return $this->tableId; }
    public function openedByUserId(): Uuid { return $this->openedByUserId; }
    public function closedByUserId(): ?Uuid { return $this->closedByUserId; }
    public function diners(): Diners { return $this->diners; }
    public function discountType(): ?string { return $this->discountType?->value; }
    public function discountValue(): int { return $this->discountValue; }
    public function discountAmount(): int { return $this->discountAmount->getValue(); }
    public function openedAt(): DomainDateTime { return $this->openedAt; }
    public function closedAt(): ?DomainDateTime { return $this->closedAt; }
    public function persistedAt(): ?DomainDateTime { return $this->persistedAt; }

    public function syncPersistedAt(DomainDateTime $persistedAt): void
    {
        $this->persistedAt = $persistedAt;
    }

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

        return Discount::calculateAmount(
            $this->discountType->value,
            $this->discountValue,
            $this->calculateLinesSubtotalAfterDiscounts($lines),
        );
    }

    private function calculateLinesSubtotalAfterDiscounts(array $lines): int
    {
        return array_reduce($lines, fn ($carry, $line) => $carry + $line->subtotalAfterDiscount(), 0);
    }
}