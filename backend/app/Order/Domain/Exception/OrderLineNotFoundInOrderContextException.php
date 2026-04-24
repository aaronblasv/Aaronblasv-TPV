<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class OrderLineNotFoundInOrderContextException extends NotFoundException
{
    public function __construct(string $lineUuid, string $orderUuid)
    {
        parent::__construct("Order line '{$lineUuid}' was not found in order '{$orderUuid}'.");
    }
}
