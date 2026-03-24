<?php

namespace App\Tax\Domain\ValueObject;

class TaxPercentage
{
    private function __construct(
        private int $value,
    ) {}

    public static function create(int $value): self
    {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Tax percentage must be between 0 and 100.');
        }
        return new self($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }
}