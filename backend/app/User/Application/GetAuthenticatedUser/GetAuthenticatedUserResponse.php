<?php

declare(strict_types=1);

namespace App\User\Application\GetAuthenticatedUser;

use App\User\Domain\Entity\User;

final readonly class GetAuthenticatedUserResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public int $restaurantId,
        public string $restaurantName,
        public ?string $imageSrc,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(User $user, ?string $restaurantName = null): self
    {
        return new self(
            id: $user->uuid()->getValue(),
            name: $user->name()->getValue(),
            email: $user->email()->getValue(),
            role: $user->role()->value,
            restaurantId: $user->restaurantId()->getValue(),
            restaurantName: $restaurantName ?? '',
            imageSrc: $user->imageSrc(),
            createdAt: $user->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'restaurant_id' => $this->restaurantId,
            'restaurant_name' => $this->restaurantName,
            'image_src' => $this->imageSrc,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}