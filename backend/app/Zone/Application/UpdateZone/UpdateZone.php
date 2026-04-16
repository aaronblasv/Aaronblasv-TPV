<?php

namespace App\Zone\Application\UpdateZone;

use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;

class UpdateZone
{
    public function __construct(
        private ZoneRepositoryInterface $repository,
    ) {}

    public function __invoke(string $uuid, string $name, int $restaurantId): UpdateZoneResponse
    {
        $zone = $this->repository->findById($uuid, $restaurantId);

        if ($zone === null) {
            throw new ZoneNotFoundException($uuid);
        }

        $zone->dddUpdate(ZoneName::create($name));

        $this->repository->save($zone);

        return UpdateZoneResponse::create($zone);
    }
}
