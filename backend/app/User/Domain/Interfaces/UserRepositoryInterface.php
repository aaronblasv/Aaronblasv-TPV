<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(string $userUuid, ?int $restaurantId = null): ?User;

    public function findByEmail(string $email): ?User;

    public function findAll(int $restaurantId): array;

    public function delete(User $user): void;

    public function findByPin(string $pin, int $restaurantId): ?User;
}
