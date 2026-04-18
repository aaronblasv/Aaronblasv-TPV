<?php

declare(strict_types=1);

namespace App\Zone\Application\GetAllZones;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class GetAllZones
{
    public function __construct(
        private ZoneRepositoryInterface $repository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $zones = $this->repository->findAll($restaurantId);

        return array_map(
            fn(Zone $zone) => GetAllZonesResponse::create($zone),
            $zones
        );
    }
}
