<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class UserNotFoundException extends NotFoundException
{
    public function __construct(string $identifier)
    {
        parent::__construct("User '{$identifier}' not found.");
    }
}
