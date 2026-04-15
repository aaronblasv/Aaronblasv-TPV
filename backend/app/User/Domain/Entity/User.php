<?php

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserRole;

class User
{
    private function __construct(
        private Uuid $id,
        private UserName $name,
        private Email $email,
        private PasswordHash $passwordHash,
        private UserRole $role,
        private int $restaurantId,
        private ?string $pin,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Email $email, UserName $name, PasswordHash $passwordHash, UserRole $role, int $restaurantId): self
    {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $name,
            $email,
            $passwordHash,
            $role,
            $restaurantId,
            str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            $now,
            $now,
        );
    }

    public function dddUpdate(UserName $name, Email $email): void
    {
        $this->name = $name;
        $this->email = $email;
        $this->updatedAt = DomainDateTime::now();
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        int $restaurantId,
        ?string $pin,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            UserName::create($name),
            Email::create($email),
            PasswordHash::create($passwordHash),
            UserRole::create($role),
            $restaurantId,
            $pin,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid { return $this->id; }
    public function name(): UserName { return $this->name; }
    public function email(): Email { return $this->email; }
    public function passwordHash(): PasswordHash { return $this->passwordHash; }
    public function role(): UserRole { return $this->role; }
    public function restaurantId(): int { return $this->restaurantId; }
    public function createdAt(): DomainDateTime { return $this->createdAt; }
    public function updatedAt(): DomainDateTime { return $this->updatedAt; }
    public function pin(): ?string { return $this->pin; }
}