<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class CannotInvoiceOpenOrderException extends BusinessRuleViolationException
{
    public function __construct(string $orderUuid)
    {
        parent::__construct("Cannot mark order '{$orderUuid}' as invoiced because it is not closed.");
    }
}