<?php

declare(strict_types=1);

namespace App\User\Application\GetAllUsers;

use App\User\Domain\Entity\User;

final readonly class GetAllUsersResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public string $email,
        public string $role,
        public ?string $imageSrc,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            uuid: $user->uuid()->getValue(),
            name: $user->name()->getValue(),
            email: $user->email()->getValue(),
            role: $user->role()->value,
            imageSrc: $user->imageSrc(),
            createdAt: $user->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $user->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'image_src' => $this->imageSrc,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}