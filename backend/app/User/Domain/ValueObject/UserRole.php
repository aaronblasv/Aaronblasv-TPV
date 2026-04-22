<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case WAITER = 'waiter';

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this === self::SUPERVISOR;
    }

    public function isWaiter(): bool
    {
        return $this === self::WAITER;
    }

    public function isBackoffice(): bool
    {
        return $this !== self::WAITER;
    }
}