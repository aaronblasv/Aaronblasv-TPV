<?php

declare(strict_types=1);

namespace App\Order\Application\CloseOrder;

use App\Order\Domain\Entity\Order;

final readonly class CloseOrderResponse
{
    private function __construct(
        public string $uuid,
        public string $status,
        public int $total,
        public int $ticketNumber,
        public string $closedByUserId,
        public string $closedAt,
    ) {}

    public static function create(Order $order, int $total, int $ticketNumber): self
    {
        return new self(
            $order->uuid()->getValue(),
            $order->status()->getValue(),
            $total,
            $ticketNumber,
            $order->closedByUserId()->getValue(),
            $order->closedAt()->format('Y-m-d H:i:s'),
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
            'total' => $this->total,
            'ticket_number' => $this->ticketNumber,
            'closed_by_user_id' => $this->closedByUserId,
            'closed_at' => $this->closedAt,
        ];
    }
}