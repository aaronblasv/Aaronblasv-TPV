<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Invoice
{
    private function __construct(
        private Uuid $uuid,
        private Uuid $orderId,
        private string $invoiceNumber,
        private int $subtotal,
        private int $taxAmount,
        private int $total,
        private DomainDateTime $issuedAt,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        Uuid $orderId,
        string $invoiceNumber,
        int $subtotal,
        int $taxAmount,
        int $total,
    ): self {
        return new self($uuid, $orderId, $invoiceNumber, $subtotal, $taxAmount, $total, DomainDateTime::now());
    }

    public static function fromPersistence(
        string $uuid,
        string $orderId,
        string $invoiceNumber,
        int $subtotal,
        int $taxAmount,
        int $total,
        \DateTimeImmutable $issuedAt,
    ): self {
        return new self(
            Uuid::create($uuid),
            Uuid::create($orderId),
            $invoiceNumber,
            $subtotal,
            $taxAmount,
            $total,
            DomainDateTime::create($issuedAt),
        );
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function orderId(): Uuid { return $this->orderId; }
    public function invoiceNumber(): string { return $this->invoiceNumber; }
    public function subtotal(): int { return $this->subtotal; }
    public function taxAmount(): int { return $this->taxAmount; }
    public function total(): int { return $this->total; }
    public function issuedAt(): DomainDateTime { return $this->issuedAt; }
}
