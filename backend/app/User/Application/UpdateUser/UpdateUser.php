<?php

namespace App\User\Application\UpdateUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserName;
use App\User\Application\UpdateUser\UpdateUserResponse;

class UpdateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $uuid, string $email, string $name, int $restaurantId): UpdateUserResponse
    {
        $user = $this->userRepository->findById($uuid);

        if ($user === null || $user->restaurantId() !== $restaurantId) {
            throw new UserNotFoundException($uuid);
        }

        $user->dddUpdate(UserName::create($name), Email::create($email));

        $this->userRepository->save($user);

        return UpdateUserResponse::create($user);
    }
}