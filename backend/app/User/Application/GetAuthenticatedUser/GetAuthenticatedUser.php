<?php

declare(strict_types=1);

namespace App\User\Application\GetAuthenticatedUser;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Application\GetAuthenticatedUser\GetAuthenticatedUserResponse;

class GetAuthenticatedUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $uuid): GetAuthenticatedUserResponse
    {
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            throw new UserNotFoundException($uuid);
        }

        $restaurantName = $this->restaurantRepository->findNameById($user->restaurantId());

        return GetAuthenticatedUserResponse::create($user, $restaurantName);
    }
}