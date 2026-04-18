<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\Pin;
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
        private RestaurantId $restaurantId,
        private ?Pin $pin,
        private ?string $imageSrc,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Email $email, UserName $name, PasswordHash $passwordHash, UserRole $role, int $restaurantId, ?string $imageSrc = null): self
    {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $name,
            $email,
            $passwordHash,
            $role,
            RestaurantId::create($restaurantId),
            Pin::create(str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT)),
            $imageSrc,
            $now,
            $now,
        );
    }

    public function dddUpdate(UserName $name, Email $email, ?string $imageSrc = null): void
    {
        $this->name = $name;
        $this->email = $email;
        $this->imageSrc = $imageSrc;
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
        ?string $imageSrc,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            UserName::create($name),
            Email::create($email),
            PasswordHash::create($passwordHash),
            UserRole::create($role),
            RestaurantId::create($restaurantId),
            $pin !== null ? Pin::create($pin) : null,
            $imageSrc,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function uuid(): Uuid { return $this->id; }
    public function name(): UserName { return $this->name; }
    public function email(): Email { return $this->email; }
    public function passwordHash(): PasswordHash { return $this->passwordHash; }
    public function role(): UserRole { return $this->role; }
    public function restaurantId(): int { return $this->restaurantId->getValue(); }
    public function restaurantIdVO(): RestaurantId { return $this->restaurantId; }
    public function createdAt(): DomainDateTime { return $this->createdAt; }
    public function updatedAt(): DomainDateTime { return $this->updatedAt; }
    public function pin(): ?string { return $this->pin?->getValue(); }
    public function pinVO(): ?Pin { return $this->pin; }
    public function imageSrc(): ?string { return $this->imageSrc; }
}