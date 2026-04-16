<?php

declare(strict_types=1);

namespace App\Family\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class FamilyNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Family '{$uuid}' not found.");
    }
}
