<?php

declare(strict_types=1);

namespace App\Tax\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class TaxNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Tax '{$uuid}' not found.");
    }
}
