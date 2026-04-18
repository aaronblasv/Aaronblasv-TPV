<?php

declare(strict_types=1);

namespace App\Sale\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;

class Sale
{
    private function __construct(
        private Uuid $uuid,
        private RestaurantId $restaurantId,
        private Uuid $orderId,
        private Uuid $userId,
        private int $ticketNumber,
        private DomainDateTime $valueDate,
        private int $subtotal,
        private int $taxAmount,
        private int $lineDiscountTotal,
        private int $orderDiscountTotal,
        private int $total,
        private int $refundedTotal,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        int $restaurantId,
        Uuid $orderId,
        Uuid $userId,
        int $ticketNumber,
        int $subtotal,
        int $taxAmount,
        int $lineDiscountTotal,
        int $orderDiscountTotal,
        int $total,
    ): self {
        return new self($uuid, RestaurantId::create($restaurantId), $orderId, $userId, $ticketNumber, DomainDateTime::now(), $subtotal, $taxAmount, $lineDiscountTotal, $orderDiscountTotal, $total, 0);
    }

    public static function fromPersistence(
        string $uuid,
        int $restaurantId,
        string $orderId,
        string $userId,
        int $ticketNumber,
        \DateTimeImmutable|DomainDateTime $valueDate,
        int $subtotal,
        int $taxAmount,
        int $lineDiscountTotal,
        int $orderDiscountTotal,
        int $total,
        int $refundedTotal,
    ): self {
        return new self(
            Uuid::create($uuid),
            RestaurantId::create($restaurantId),
            Uuid::create($orderId),
            Uuid::create($userId),
            $ticketNumber,
            $valueDate instanceof DomainDateTime ? $valueDate : DomainDateTime::create($valueDate),
            $subtotal,
            $taxAmount,
            $lineDiscountTotal,
            $orderDiscountTotal,
            $total,
            $refundedTotal,
        );
    }

    public function registerRefund(int $amount): void
    {
        $this->refundedTotal += max(0, $amount);
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function orderId(): Uuid { return $this->orderId; }
    public function userId(): Uuid { return $this->userId; }
    public function ticketNumber(): int { return $this->ticketNumber; }
    public function valueDate(): DomainDateTime { return $this->valueDate; }
    public function subtotal(): int { return $this->subtotal; }
    public function taxAmount(): int { return $this->taxAmount; }
    public function lineDiscountTotal(): int { return $this->lineDiscountTotal; }
    public function orderDiscountTotal(): int { return $this->orderDiscountTotal; }
    public function total(): int { return $this->total; }
    public function refundedTotal(): int { return $this->refundedTotal; }
    public function netTotal(): int { return max(0, $this->total - $this->refundedTotal); }
}