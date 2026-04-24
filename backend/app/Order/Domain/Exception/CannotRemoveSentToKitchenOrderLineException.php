<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class CannotRemoveSentToKitchenOrderLineException extends BusinessRuleViolationException
{
    public function __construct(string $lineUuid)
    {
        parent::__construct("Order line '{$lineUuid}' has already been sent to kitchen and must be voided from the summary.");
    }
}
