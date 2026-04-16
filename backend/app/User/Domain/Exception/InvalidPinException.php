<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InvalidPinException extends UnauthorizedException
{
    public function __construct()
    {
        parent::__construct('Invalid PIN.');
    }
}
