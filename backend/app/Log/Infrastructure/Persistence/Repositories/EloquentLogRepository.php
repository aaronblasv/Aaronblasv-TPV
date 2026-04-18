<?php

declare(strict_types=1);

namespace App\Log\Infrastructure\Persistence\Repositories;

use App\Log\Domain\Entity\Log;
use App\Log\Domain\Interfaces\LogRepositoryInterface;
use App\Log\Infrastructure\Persistence\Models\EloquentLog;

class EloquentLogRepository implements LogRepositoryInterface
{
    public function __construct(private EloquentLog $model) {}

    public function save(Log $log): void
    {
        $this->model->newQuery()->create([
            'uuid'        => $log->uuid()->getValue(),
            'restaurant_id' => $log->restaurantId(),
            'user_id'     => $log->userId(),
            'action'      => $log->action(),
            'entity_type' => $log->entityType(),
            'entity_uuid' => $log->entityUuid(),
            'data'        => $log->data(),
            'ip_address'  => $log->ipAddress(),
        ]);
    }

    public function findAll(int $restaurantId, int $limit = 50, int $offset = 0): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn(EloquentLog $log) => $this->toDomain($log))
            ->toArray();
    }

    public function findByUser(int $restaurantId, string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn(EloquentLog $log) => $this->toDomain($log))
            ->toArray();
    }

    public function findByAction(int $restaurantId, string $action, int $limit = 50, int $offset = 0): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn(EloquentLog $log) => $this->toDomain($log))
            ->toArray();
    }

    public function findByEntity(int $restaurantId, string $entityType, string $entityUuid, int $limit = 50, int $offset = 0): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('entity_type', $entityType)
            ->where('entity_uuid', $entityUuid)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(fn(EloquentLog $log) => $this->toDomain($log))
            ->toArray();
    }

    public function count(int $restaurantId): int
    {
        return $this->model->newQuery()->where('restaurant_id', $restaurantId)->count();
    }

    private function toDomain(EloquentLog $model): Log
    {
        return Log::fromPersistence(
            $model->uuid,
            $model->restaurant_id,
            $model->user_id,
            $model->action,
            $model->entity_type,
            $model->entity_uuid,
            $model->data,
            $model->ip_address,
            new \DateTimeImmutable($model->created_at),
        );
    }
}
