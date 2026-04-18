<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Listener;

use App\Log\Application\CreateLog\CreateLog;
use App\Shared\Domain\Event\ActionLogged;

class WriteLogOnActionLogged
{
    public function __construct(private CreateLog $createLog) {}

    public function handle(ActionLogged $event): void
    {
        ($this->createLog)(
            $event->restaurantId,
            $event->userId,
            $event->action,
            $event->entityType,
            $event->entityUuid,
            $event->data,
            $event->ipAddress,
        );
    }
}
