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
        public string $openedAt,
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
            $order->openedAt()->format('Y-m-d H:i:s'),
            array_map(fn(OrderLine $line) => [
                'uuid'          => $line->uuid()->getValue(),
                'productId'     => $line->productId()->getValue(),
                'userId'        => $line->userId()->getValue(),
                'quantity'      => $line->quantity()->getValue(),
                'price'         => $line->price(),
                'taxPercentage' => $line->taxPercentage(),
            ], $lines),
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
            'opened_at' => $this->openedAt,
            'lines' => array_map(static fn(array $line) => [
                'uuid' => $line['uuid'] ?? null,
                'product_id' => $line['productId'] ?? null,
                'user_id' => $line['userId'] ?? null,
                'quantity' => $line['quantity'] ?? null,
                'price' => $line['price'] ?? null,
                'tax_percentage' => $line['taxPercentage'] ?? null,
            ], $this->lines),
        ];
    }
}