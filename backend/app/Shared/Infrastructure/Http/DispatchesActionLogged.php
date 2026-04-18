<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\Event\ActionLogged;

trait DispatchesActionLogged
{
    protected function logAction(
        ?int $restaurantId,
        ?string $userId,
        string $action,
        ?string $entityType = null,
        ?string $entityUuid = null,
        ?array $data = null,
        ?string $ipAddress = null,
    ): void {
        event(ActionLogged::create(
            $restaurantId,
            $userId,
            $action,
            $entityType,
            $entityUuid,
            $data,
            $ipAddress,
        ));
    }
}
