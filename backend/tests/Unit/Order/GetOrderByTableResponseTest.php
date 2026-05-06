<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\GetOrderByTable\GetOrderByTableResponse;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class GetOrderByTableResponseTest extends TestCase
{
    public function test_it_uses_freshly_computed_order_discount_amount(): void
    {
        $order = Order::fromPersistence(
            Uuid::generate()->getValue(),
            1,
            'open',
            Uuid::generate()->getValue(),
            Uuid::generate()->getValue(),
            null,
            2,
            'percentage',
            10,
            0,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
            null,
            new \DateTimeImmutable('2026-04-27 10:00:00'),
        );

        $line = OrderLine::dddCreate(
            Uuid::generate(),
            1,
            $order->uuid(),
            Uuid::generate(),
            Uuid::generate(),
            Quantity::create(1),
            1000,
            10,
        );

        $response = GetOrderByTableResponse::create($order, [$line], 250);

        $this->assertSame(100, $response->discountAmount);
        $this->assertSame(250, $response->totalPaid);
    }
}