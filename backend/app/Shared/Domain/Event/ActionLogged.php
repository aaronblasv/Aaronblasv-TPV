<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

class ActionLogged extends DomainEvent
{
    public static function create(
        ?int $restaurantId,
        ?string $userId,
        string $action,
        ?string $entityType = null,
        ?string $entityUuid = null,
        ?array $data = null,
        ?string $ipAddress = null,
    ): self {
        return new self($restaurantId, $userId, $action, $entityType, $entityUuid, $data, $ipAddress);
    }
}
