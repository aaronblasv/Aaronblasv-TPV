<?php

declare(strict_types=1);

namespace App\Order\Application\GetAllOpenOrders;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderLine;

final readonly class GetAllOpenOrdersResponse
{
    private function __construct(
        public string $uuid,
        public string $status,
        public string $tableId,
        public string $openedByUserId,
        public int $diners,
        public ?string $discountType,
        public int $discountValue,
        public int $discountAmount,
        public int $subtotal,
        public int $taxAmount,
        public int $total,
        public string $openedAt,
    ) {}

    /**
     * @param OrderLine[] $lines
     */
    public static function create(Order $order, array $lines): self
    {
        $subtotal = $order->calculateSubtotal($lines);
        $taxAmount = $order->calculateTaxAmount($lines);

        return new self(
            $order->uuid()->getValue(),
            $order->status()->getValue(),
            $order->tableId()->getValue(),
            $order->openedByUserId()->getValue(),
            $order->diners()->getValue(),
            $order->discountType(),
            $order->discountValue(),
            $order->discountAmount(),
            $subtotal,
            $taxAmount,
            $subtotal + $taxAmount,
            $order->openedAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'table_id' => $this->tableId,
            'opened_by_user_id' => $this->openedByUserId,
            'diners' => $this->diners,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'discount_amount' => $this->discountAmount,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total' => $this->total,
            'opened_at' => $this->openedAt,
        ];
    }
}
