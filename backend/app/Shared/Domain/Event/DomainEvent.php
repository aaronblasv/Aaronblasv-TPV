<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

abstract class DomainEvent
{
    public readonly string $occurredAt;

    public function __construct(
        public readonly ?int $restaurantId,
        public readonly ?string $userId,
        public readonly string $action,
        public readonly ?string $entityType,
        public readonly ?string $entityUuid,
        public readonly ?array $data,
        public readonly ?string $ipAddress,
    ) {
        $this->occurredAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
