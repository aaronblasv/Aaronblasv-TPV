<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

class ProductStock
{
    private function __construct(
        private int $value,
    ) {}

    public static function create(int $value): self
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Product stock cannot be negative.');
        }

        return new self($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }
}