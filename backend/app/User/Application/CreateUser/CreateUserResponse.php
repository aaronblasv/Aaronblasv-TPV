<?php

declare(strict_types=1);

namespace App\User\Application\CreateUser;

use App\User\Domain\Entity\User;

final readonly class CreateUserResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $pin,
        public ?string $imageSrc,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            id: $user->uuid()->getValue(),
            name: $user->name()->getValue(),
            email: $user->email()->getValue(),
            pin: $user->pin(),
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
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'pin' => $this->pin,
            'image_src' => $this->imageSrc,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
