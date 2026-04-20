<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderByTable;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderLine;

final readonly class GetOrderByTableResponse
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
        public string $openedAt,
        /** @var OrderLine[] */
        public array $lines,
    ) {}

    public static function create(Order $order, array $lines): self
    {
        return new self(
            $order->uuid()->getValue(),
            $order->status()->getValue(),
            $order->tableId()->getValue(),
            $order->openedByUserId()->getValue(),
            $order->diners()->getValue(),
            $order->discountType(),
            $order->discountValue(),
            $order->discountAmount(),
            $order->openedAt()->format('Y-m-d H:i:s'),
            $lines,
        );
    }

    /**
     * @return array<string, mixed>
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
            'opened_at' => $this->openedAt,
            'lines' => array_map(static fn(OrderLine $line) => [
                'uuid' => $line->uuid()->getValue(),
                'product_id' => $line->productId()->getValue(),
                'user_id' => $line->userId()->getValue(),
                'quantity' => $line->quantity()->getValue(),
                'price' => $line->price(),
                'tax_percentage' => $line->taxPercentage(),
                'discount_type' => $line->discountType(),
                'discount_value' => $line->discountValue(),
                'discount_amount' => $line->discountAmount(),
            ], $this->lines),
        ];
    }
}