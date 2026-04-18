<?php

declare(strict_types=1);

namespace App\User\Application\DeleteUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $uuid, int $restaurantId): void
    {
        $user = $this->userRepository->findById($uuid);

        if ($user === null || $user->restaurantId() !== $restaurantId) {
            throw new UserNotFoundException($uuid);
        }

        $this->userRepository->delete($user);
    }
}