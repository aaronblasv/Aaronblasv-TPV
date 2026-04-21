<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\Discount;
use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase
{
    public function test_calculates_percentage_discount(): void
    {
        $discountAmount = Discount::calculateAmount('percentage', 25, 2000);

        $this->assertSame(500, $discountAmount);
    }

    public function test_calculates_amount_discount_capped_to_base_amount(): void
    {
        $discountAmount = Discount::calculateAmount('amount', 2500, 2000);

        $this->assertSame(2000, $discountAmount);
    }

    public function test_rejects_invalid_discount_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Discount::calculateAmount('bogus', 10, 1000);
    }
}