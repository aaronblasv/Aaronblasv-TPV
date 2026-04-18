<?php

declare(strict_types=1);

namespace App\Zone\Application\CreateZone;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use App\Shared\Domain\ValueObject\Uuid;

class CreateZone
{
    public function __construct(
        private ZoneRepositoryInterface $repository,
    ) {}

    public function __invoke(string $name, int $restaurantId): CreateZoneResponse
    {
        $zone = Zone::dddCreate(
            Uuid::generate(),
            ZoneName::create($name),
            $restaurantId,
        );

        $this->repository->save($zone);

        return CreateZoneResponse::create($zone);
    }
}
