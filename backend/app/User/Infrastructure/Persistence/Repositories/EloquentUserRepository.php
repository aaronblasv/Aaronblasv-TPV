<?php

namespace App\User\Infrastructure\Persistence\Repositories;

use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EloquentUser $model,
    ) {}

    public function save(User $user): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $user->id()->getValue()],
            [
                'name' => $user->name(),
                'email' => $user->email()->getValue(),
                'password' => $user->passwordHash(),
                'role' => $user->role()->getValue(),
                'restaurant_id' => $user->restaurantId(),
                'created_at' => $user->createdAt()->getValue(),
                'updated_at' => $user->updatedAt()->getValue(),
            ]
        );
    }

    public function findAll(): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', auth()->user()?->restaurant_id)
            ->get()
            ->map(fn(EloquentUser $model) => User::fromPersistence(
                $model->uuid,
                $model->name,
                $model->email,
                $model->password,
                $model->role,
                $model->restaurant_id,
                $model->created_at->toDateTimeImmutable(),
                $model->updated_at->toDateTimeImmutable(),
            ))->toArray();
    }

    public function findById(string $id): ?User
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        if ($model === null) {
            return null;
        }

        return User::fromPersistence(
            $model->uuid,
            $model->name,
            $model->email,
            $model->password,
            $model->role,
            $model->restaurant_id,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findByEmail(string $email): ?User
    {
        $model = $this->model->newQuery()->where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return User::fromPersistence(
            $model->uuid,
            $model->name,
            $model->email,
            $model->password,
            $model->role,
            $model->restaurant_id,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function delete(User $user): void
    {
        $this->model->newQuery()->where('uuid', $user->id()->getValue())->delete();
    }
}