<?php

declare(strict_types=1);

namespace App\Log\Application\GetLogs;

use App\Log\Domain\Entity\Log;

final readonly class GetLogsResponse
{
    private function __construct(
        public array $logs,
        public int $total,
    ) {}

    public static function create(array $logs, int $total): self
    {
        return new self(
            array_map(fn(Log $log) => [
                'uuid'        => $log->uuid()->getValue(),
                'user_id'     => $log->userId(),
                'action'      => $log->action(),
                'entity_type' => $log->entityType(),
                'entity_uuid' => $log->entityUuid(),
                'data'        => $log->data(),
                'ip_address'  => $log->ipAddress(),
                'created_at'  => $log->createdAt()->format('Y-m-d H:i:s'),
            ], $logs),
            $total,
        );
    }

    /**
     * @return array{logs: array<int, array<string, mixed>>, total: int}
     */
    public function toArray(): array
    {
        return [
            'logs' => $this->logs,
            'total' => $this->total,
        ];
    }
}
