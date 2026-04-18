<?php

declare(strict_types=1);

namespace App\User\Application\GetUserById;

use App\User\Domain\Entity\User;

final readonly class GetUserByIdResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public string $email,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            uuid: $user->uuid()->getValue(),
            name: $user->name()->getValue(),
            email: $user->email()->getValue(),
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
        ];
    }
}