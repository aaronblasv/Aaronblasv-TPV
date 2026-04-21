<?php

declare(strict_types=1);

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
            ['uuid' => $user->uuid()->getValue()],
            [
                'name' => $user->name()->getValue(),
                'email' => $user->email()->getValue(),
                'password' => $user->passwordHash()->getValue(),
                'role' => $user->role()->getValue(),
                'restaurant_id' => $user->restaurantId()->getValue(),
                'pin' => $user->pin()?->getValue(),
                'image_src' => $user->imageSrc(),
                'created_at' => $user->createdAt()->getValue(),
                'updated_at' => $user->updatedAt()->getValue(),
            ]
        );
    }

    public function findAll(int $restaurantId): array
    {
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->map(fn(EloquentUser $model) => User::fromPersistence(
                $model->uuid,
                $model->name,
                $model->email,
                $model->password,
                $model->role,
                $model->restaurant_id,
                $model->pin,
                $model->image_src,
                $model->created_at->toDateTimeImmutable(),
                $model->updated_at->toDateTimeImmutable(),
            ))->toArray();
    }

    public function findById(string $userUuid, ?int $restaurantId = null): ?User
    {
        $query = $this->model->newQuery()->where('uuid', $userUuid);

        if ($restaurantId !== null) {
            $query->where('restaurant_id', $restaurantId);
        }

        $model = $query->first();

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
            $model->pin,
            $model->image_src,
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
            $model->pin,
            $model->image_src,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function delete(User $user): void
    {
        $this->model->newQuery()->where('uuid', $user->uuid()->getValue())->delete();
    }

    public function findByPin(string $pin, int $restaurantId): ?User
    {
        $model = $this->model->newQuery()
            ->where('pin', $pin)
            ->where('restaurant_id', $restaurantId)
            ->first();

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
            $model->pin,
            $model->image_src,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}