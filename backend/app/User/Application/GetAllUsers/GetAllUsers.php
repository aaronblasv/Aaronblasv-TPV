<?php

declare(strict_types=1);

namespace App\User\Application\GetAllUsers;

use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetAllUsers
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(int $restaurantId): array
    {
        $users = $this->userRepository->findAll($restaurantId);

        return array_map(
            fn($user) => GetAllUsersResponse::create($user),
            $users
        );
    }
}