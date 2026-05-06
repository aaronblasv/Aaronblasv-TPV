<?php

declare(strict_types=1);

namespace Tests\Unit\Tax;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxPercentage;
use PHPUnit\Framework\TestCase;

class TaxPercentageTest extends TestCase
{
    public function test_it_converts_decimal_percentage_to_basis_points(): void
    {
        $percentage = TaxPercentage::fromPercentage(7.5);

        $this->assertSame(750, $percentage->basisPoints());
        $this->assertSame(7.5, $percentage->asPercentage());
    }

    public function test_order_line_tax_amount_supports_decimal_tax_percentages(): void
    {
        $line = OrderLine::dddCreate(
            Uuid::generate(),
            1,
            Uuid::generate(),
            Uuid::generate(),
            Uuid::generate(),
            Quantity::create(1),
            333,
            TaxPercentage::fromPercentage(7.5)->basisPoints(),
        );

        $this->assertSame(25, $line->taxAmount());
        $this->assertSame(7.5, $line->taxPercentageAsPercentage());
    }
}
