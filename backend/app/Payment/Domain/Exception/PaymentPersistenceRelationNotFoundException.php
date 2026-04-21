<?php

declare(strict_types=1);

namespace App\Payment\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class PaymentPersistenceRelationNotFoundException extends DomainException
{
    public static function missingOrder(string $paymentUuid, int $orderId): self
    {
        return new self("Payment '{$paymentUuid}' references missing order id '{$orderId}'.");
    }

    public static function missingUser(string $paymentUuid, int $userId): self
    {
        return new self("Payment '{$paymentUuid}' references missing user id '{$userId}'.");
    }
}