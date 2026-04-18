<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;

class OrderClosed
{
    public function __construct(
        public readonly Uuid $orderUuid,
        public readonly int $restaurantId,
        public readonly Uuid $closedByUserUuid,
        public readonly int $subtotal,
        public readonly int $taxAmount,
        public readonly int $lineDiscountTotal,
        public readonly int $orderDiscountTotal,
        public readonly int $total,
        public readonly array $lines,
    ) {}
}
