<?php

declare(strict_types=1);

namespace App\User\Application\UpdateUser;

use App\User\Domain\Entity\User;

final readonly class UpdateUserResponse
{
    private function __construct(
        public string $uuid,
        public string $name,
        public string $email,
        public ?string $imageSrc,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            uuid: $user->uuid()->getValue(),
            name: $user->name()->getValue(),
            email: $user->email()->getValue(),
            imageSrc: $user->imageSrc(),
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
            'image_src' => $this->imageSrc,
        ];
    }
}