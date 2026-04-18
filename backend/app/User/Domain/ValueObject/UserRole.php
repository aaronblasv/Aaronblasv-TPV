<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

class UserRole
{
    public const ADMIN = 'admin';
    public const SUPERVISOR = 'supervisor';
    public const WAITER = 'waiter';

    private const VALID_ROLES = [self::ADMIN, self::SUPERVISOR, self::WAITER];

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