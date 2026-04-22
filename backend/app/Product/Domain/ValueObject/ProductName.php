<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

class ProductName
{

    private function __construct(
        private string $value,
    ) {
        $this->validate();
    }
    
    public static function create(string $value): self
    {
        return new self($value);
    }

    private function validate(): void
    {
        if (trim($this->value) === '') {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

}