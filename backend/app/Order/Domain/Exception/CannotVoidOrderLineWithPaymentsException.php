<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class CannotVoidOrderLineWithPaymentsException extends BusinessRuleViolationException
{
    public function __construct(string $orderUuid)
    {
        parent::__construct("Order '{$orderUuid}' already has registered payments, so sent lines cannot be voided.");
    }
}
