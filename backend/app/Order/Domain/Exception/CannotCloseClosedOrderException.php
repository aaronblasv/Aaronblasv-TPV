<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class CannotCloseClosedOrderException extends BusinessRuleViolationException
{
    public function __construct(string $orderUuid)
    {
        parent::__construct("Cannot close order '{$orderUuid}' because it is not open.");
    }
}