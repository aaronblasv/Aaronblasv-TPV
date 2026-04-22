<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_from_hydrates_native_enum_and_helper_methods(): void
    {
        $role = UserRole::from('waiter');

        $this->assertSame(UserRole::WAITER, $role);
        $this->assertTrue($role->isWaiter());
        $this->assertFalse($role->isBackoffice());
        $this->assertSame('waiter', $role->value);
    }
}