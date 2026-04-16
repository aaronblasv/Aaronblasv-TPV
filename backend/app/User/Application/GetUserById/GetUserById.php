<?php

namespace App\User\Application\GetUserById;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Application\GetUserById\GetUserByIdResponse;

class GetUserById
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $uuid): GetUserByIdResponse
    {
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            throw new UserNotFoundException($uuid);
        }

        return GetUserByIdResponse::create($user);
    }
}