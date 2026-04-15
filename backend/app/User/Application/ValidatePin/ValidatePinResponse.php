<?php

declare(strict_types=1);

namespace App\User\Application\ValidatePin;

use App\User\Domain\Entity\User;

final readonly class ValidatePinResponse
{
    private function __construct(
        public string $uuid,
        public string $id,
        public string $name,
        public string $role,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            uuid: $user->id()->getValue(),
            id: $user->id()->getValue(),
            name: $user->name()->getValue(),
            role: $user->role()->getValue(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
        ];
    }
}