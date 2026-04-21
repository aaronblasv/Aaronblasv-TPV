<?php

declare(strict_types=1);

namespace App\Table\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class InvalidTableMergeException extends BusinessRuleViolationException
{
    public function __construct(string $message = 'Solo se puede unir una mesa con pedido abierto con una o varias mesas vacías.')
    {
        parent::__construct($message);
    }
}