<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class OrderStatus
{
    public const OPEN = 'open';
    public const CLOSED = 'closed';
    public const CANCELLED = 'cancelled';
    public const INVOICED = 'invoiced';
    private const VALID_STATUSES = [self::OPEN, self::CLOSED, self::CANCELLED, self::INVOICED];

    private function __construct(private string $value) {}

    public static function open(): self
    {
        return new self(self::OPEN);
    }

    public static function closed(): self
    {
        return new self(self::CLOSED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function invoiced(): self
    {
        return new self(self::INVOICED);
    }

    public static function create(string $value): self
    {
        if (!in_array($value, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Invalid order status: {$value}");
        }
        return new self($value);
    }

    public function isOpen(): bool
    {
        return $this->value === self::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->value === self::CLOSED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isInvoiced(): bool
    {
        return $this->value === self::INVOICED;
    }

    public function getValue(): string { return $this->value; }
}