<?php

namespace App\User\Application\GetAllUsers;

use App\User\Domain\Entity\User;

final readonly class GetAllUsersResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            id: $user->id()->getValue(),
            name: $user->name()->getValue(),
            email: $user->email()->getValue(),
            role: $user->role()->getValue(),
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
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}