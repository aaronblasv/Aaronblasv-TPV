<?php

declare(strict_types=1);

namespace App\Zone\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class ZoneNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Zone '{$uuid}' not found.");
    }
}
