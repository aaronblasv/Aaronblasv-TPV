<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class CannotVoidPendingOrderLineException extends BusinessRuleViolationException
{
    public function __construct(string $lineUuid)
    {
        parent::__construct("Order line '{$lineUuid}' has not been sent to kitchen and should be removed from the order tab instead.");
    }
}
