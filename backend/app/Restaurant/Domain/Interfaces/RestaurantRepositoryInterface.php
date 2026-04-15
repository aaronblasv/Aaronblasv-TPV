<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Interfaces;

interface RestaurantRepositoryInterface
{
    public function findNameById(int $restaurantId): ?string;
}

