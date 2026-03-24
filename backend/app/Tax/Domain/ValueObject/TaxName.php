<?php

namespace App\Tax\Domain\ValueObject;

class TaxName
{
    private function __construct(
        private string $value,
    ) {}

    public static function create(string $value): self
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Tax name cannot be empty.');
        }
        return new self($value);
    }

    public function GetValue(): string
    {
        return $this->value;
    }
}