<?php

declare(strict_types=1);

namespace App\Restaurant\Infrastructure\Persistence\Repositories;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;

final readonly class EloquentRestaurantRepository implements RestaurantRepositoryInterface
{
    public function __construct(
        private EloquentRestaurant $model,
    ) {}

    public function findNameById(int $restaurantId): ?string
    {
        $name = $this->model->newQuery()
            ->where('id', $restaurantId)
            ->value('name');

        return $name !== null ? (string) $name : null;
    }
}

