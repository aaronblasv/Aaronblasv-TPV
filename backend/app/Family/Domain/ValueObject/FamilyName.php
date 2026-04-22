<?php

declare(strict_types=1);

namespace App\Family\Domain\ValueObject;

class FamilyName
{

    private function __construct(
        private string $value,
    ) {}

    public static function create(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Family name cannot be empty.');
        }
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

}