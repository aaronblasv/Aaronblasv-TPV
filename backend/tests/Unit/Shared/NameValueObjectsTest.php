<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Family\Domain\ValueObject\FamilyName;
use App\Product\Domain\ValueObject\ProductName;
use App\Table\Domain\ValueObject\TableName;
use App\Tax\Domain\ValueObject\TaxName;
use App\Zone\Domain\ValueObject\ZoneName;
use PHPUnit\Framework\TestCase;

class NameValueObjectsTest extends TestCase
{
    public function test_name_value_objects_accept_zero_as_valid_name(): void
    {
        $this->assertSame('0', TableName::create('0')->getValue());
        $this->assertSame('0', ProductName::create('0')->getValue());
        $this->assertSame('0', FamilyName::create('0')->getValue());
        $this->assertSame('0', TaxName::create('0')->getValue());
        $this->assertSame('0', ZoneName::create('0')->getValue());
    }

    public function test_name_value_objects_reject_blank_strings(): void
    {
        $constructors = [
            static fn () => TableName::create(" \t\n "),
            static fn () => ProductName::create(" \t\n "),
            static fn () => FamilyName::create(" \t\n "),
            static fn () => TaxName::create(" \t\n "),
            static fn () => ZoneName::create(" \t\n "),
        ];

        foreach ($constructors as $constructor) {
            try {
                $constructor();
                $this->fail('Expected InvalidArgumentException for blank string.');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}