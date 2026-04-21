<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class OrderPersistenceRelationNotFoundException extends DomainException
{
    public static function missingTable(string $orderUuid, int $tableId): self
    {
        return new self("Order '{$orderUuid}' references missing table id '{$tableId}'.");
    }

    public static function missingOpenedByUser(string $orderUuid, int $userId): self
    {
        return new self("Order '{$orderUuid}' references missing opened-by user id '{$userId}'.");
    }

    public static function missingClosedByUser(string $orderUuid, int $userId): self
    {
        return new self("Order '{$orderUuid}' references missing closed-by user id '{$userId}'.");
    }
}