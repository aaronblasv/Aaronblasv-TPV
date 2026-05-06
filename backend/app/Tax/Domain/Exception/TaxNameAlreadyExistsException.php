<?php

declare(strict_types=1);

namespace App\Tax\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class TaxNameAlreadyExistsException extends BusinessRuleViolationException
{
    public function __construct(string $name)
    {
        parent::__construct("Tax name '{$name}' already exists.");
    }
}
