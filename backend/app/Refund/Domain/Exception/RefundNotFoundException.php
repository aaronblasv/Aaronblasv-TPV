<?php

declare(strict_types=1);

namespace App\Refund\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class RefundNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Refund '{$uuid}' not found.");
    }
}
