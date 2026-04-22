<?php

declare(strict_types=1);

namespace App\Table\Domain\ValueObject;

class TableName
{
    private function __construct(
        private string $value,
    ) {}

    public static function create(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }

        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}