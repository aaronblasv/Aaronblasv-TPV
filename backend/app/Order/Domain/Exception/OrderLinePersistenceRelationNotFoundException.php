<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class OrderLinePersistenceRelationNotFoundException extends DomainException
{
    public static function missingOrder(string $orderLineUuid, int $orderId): self
    {
        return new self("Order line '{$orderLineUuid}' references missing order id '{$orderId}'.");
    }

    public static function missingProduct(string $orderLineUuid, int $productId): self
    {
        return new self("Order line '{$orderLineUuid}' references missing product id '{$productId}'.");
    }

    public static function missingUser(string $orderLineUuid, int $userId): self
    {
        return new self("Order line '{$orderLineUuid}' references missing user id '{$userId}'.");
    }
}