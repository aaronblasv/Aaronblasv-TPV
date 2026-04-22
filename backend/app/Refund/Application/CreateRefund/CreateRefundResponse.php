<?php

declare(strict_types=1);

namespace App\Refund\Application\CreateRefund;

use App\Refund\Domain\Entity\Refund;

final readonly class CreateRefundResponse
{
    private function __construct(
        public string $refundUuid,
        public string $refundType,
        public string $method,
        public int $subtotal,
        public int $taxAmount,
        public int $totalAmount,
    ) {}

    public static function create(Refund $refund): self
    {
        return new self(
            refundUuid: $refund->uuid()->getValue(),
            refundType: $refund->type(),
            method: $refund->method(),
            subtotal: $refund->subtotal(),
            taxAmount: $refund->taxAmount(),
            totalAmount: $refund->total(),
        );
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->refundUuid,
            'type' => $this->refundType,
            'method' => $this->method,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total' => $this->totalAmount,
        ];
    }
}