<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class OrderNotFoundException extends NotFoundException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Order '{$uuid}' not found.");
    }
}
