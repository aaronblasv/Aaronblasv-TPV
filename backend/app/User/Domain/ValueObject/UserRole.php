<?php

namespace App\User\Domain\ValueObject;

class UserRole
{
    private const VALID_ROLES = ['admin', 'supervisor', 'waiter'];

    private function __construct(private string $value) {}

    public static function create(string $value): self
    {
        if (!in_array($value, self::VALID_ROLES)) {
            throw new \InvalidArgumentException("Invalid role: {$value}");
        }
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}