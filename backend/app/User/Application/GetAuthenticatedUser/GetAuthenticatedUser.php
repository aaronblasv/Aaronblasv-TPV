<?php

namespace App\User\Application\GetAuthenticatedUser;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
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

        if($user === null) {
            throw new \Exception('User not found');
        }

        $restaurantName = $this->restaurantRepository->findNameById($user->restaurantId());

        return GetAuthenticatedUserResponse::create($user, $restaurantName);
    }
}