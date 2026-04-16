<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class TableAlreadyHasOpenOrderException extends BusinessRuleViolationException
{
    public function __construct(string $tableUuid)
    {
        parent::__construct("Table '{$tableUuid}' already has an open order.");
    }
}
