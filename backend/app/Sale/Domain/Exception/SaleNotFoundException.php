<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class SaleNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Sale '{$uuid}' not found.");
    }
}