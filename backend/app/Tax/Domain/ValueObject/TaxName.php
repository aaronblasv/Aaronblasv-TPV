<?php

declare(strict_types=1);

namespace App\Tax\Domain\ValueObject;

class TaxName
{
    private function __construct(
        private string $value,
    ) {}

    public static function create(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Tax name cannot be empty.');
        }
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}