<?php

declare(strict_types=1);

namespace App\Tax\Domain\ValueObject;

class TaxPercentage
{
    private function __construct(
        private int $value,
    ) {}

    public static function create(int $value): self
    {
        if ($value < 0 || $value > 10000) {
            throw new \InvalidArgumentException('Tax percentage must be between 0 and 100.');
        }

        return new self($value);
    }

    public static function fromPercentage(float $percentage): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Tax percentage must be between 0 and 100.');
        }

        return new self((int) round($percentage * 100));
    }

    public function basisPoints(): int
    {
        return $this->value;
    }

    public function asPercentage(): float
    {
        return $this->value / 100;
    }

    public function getValue(): int
    {
        return $this->basisPoints();
    }
}