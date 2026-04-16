<?php

declare(strict_types=1);

namespace App\Table\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class TableNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Table '{$uuid}' not found.");
    }
}
