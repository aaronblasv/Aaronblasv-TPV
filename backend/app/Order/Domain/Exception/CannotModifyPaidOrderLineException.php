<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class CannotModifyPaidOrderLineException extends DomainException
{
    public function __construct(string $lineUuid)
    {
        parent::__construct(sprintf('Order line "%s" has already been paid and cannot be modified.', $lineUuid));
    }
}