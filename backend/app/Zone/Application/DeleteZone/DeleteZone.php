<?php

namespace App\Zone\Application\DeleteZone;

use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class DeleteZone
{
    public function __construct(
        private ZoneRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, int $restaurantId): void
    {
        $zone = $this->repository->findById($uuid, $restaurantId);

        if ($zone === null) {
            throw new ZoneNotFoundException($uuid);
        }

        $this->repository->delete($uuid, $restaurantId);
    }
}
