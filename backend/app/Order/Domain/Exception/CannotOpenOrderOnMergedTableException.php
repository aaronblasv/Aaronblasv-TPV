<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\BusinessRuleViolationException;

final class CannotOpenOrderOnMergedTableException extends BusinessRuleViolationException
{
    public function __construct()
    {
        parent::__construct('No se puede abrir un pedido en una mesa agrupada. Accede desde la mesa principal.');
    }
}
