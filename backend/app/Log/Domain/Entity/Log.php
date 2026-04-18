<?php

declare(strict_types=1);

namespace App\Log\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Log
{
    private function __construct(
        private Uuid $uuid,
        private ?int $restaurantId,
        private ?string $userId,
        private string $action,
        private ?string $entityType,
        private ?string $entityUuid,
        private ?array $data,
        private ?string $ipAddress,
        private DomainDateTime $createdAt,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        ?int $restaurantId,
        ?string $userId,
        string $action,
        ?string $entityType = null,
        ?string $entityUuid = null,
        ?array $data = null,
        ?string $ipAddress = null,
    ): self {
        return new self(
            $uuid,
            $restaurantId,
            $userId,
            $action,
            $entityType,
            $entityUuid,
            $data,
            $ipAddress,
            DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $uuid,
        ?int $restaurantId,
        ?string $userId,
        string $action,
        ?string $entityType,
        ?string $entityUuid,
        ?array $data,
        ?string $ipAddress,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            Uuid::create($uuid),
            $restaurantId,
            $userId,
            $action,
            $entityType,
            $entityUuid,
            $data,
            $ipAddress,
            DomainDateTime::create($createdAt),
        );
    }

    public function uuid(): Uuid { return $this->uuid; }
    public function restaurantId(): ?int { return $this->restaurantId; }
    public function userId(): ?string { return $this->userId; }
    public function action(): string { return $this->action; }
    public function entityType(): ?string { return $this->entityType; }
    public function entityUuid(): ?string { return $this->entityUuid; }
    public function data(): ?array { return $this->data; }
    public function ipAddress(): ?string { return $this->ipAddress; }
    public function createdAt(): DomainDateTime { return $this->createdAt; }
}
