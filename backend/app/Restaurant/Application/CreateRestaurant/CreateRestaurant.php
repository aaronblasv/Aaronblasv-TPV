<?php

declare(strict_types=1);

namespace App\Restaurant\Application\CreateRestaurant;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\RestaurantId;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\PinGeneratorInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Email;

class CreateRestaurant
{
    public function __construct(
        private RestaurantRepositoryInterface $restaurantRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PinGeneratorInterface $pinGenerator,
    ) {}

    public function __invoke(
        string $name,
        string $legalName,
        string $taxId,
        string $restaurantEmail,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
    ): CreateRestaurantResponse {
        $restaurantId = $this->restaurantRepository->create(
            $name,
            $legalName,
            $taxId,
            $restaurantEmail,
        );

        $admin = User::dddCreate(
            Email::create($adminEmail),
            UserName::create($adminName),
            PasswordHash::create($this->passwordHasher->hash($adminPassword)),
            UserRole::from('admin'),
            RestaurantId::create($restaurantId),
            $this->pinGenerator->generate(),
        );

        $this->userRepository->save($admin);

        return new CreateRestaurantResponse(
            restaurantId: $restaurantId,
            restaurantName: $name,
            adminUuid: $admin->uuid()->getValue(),
            adminEmail: $adminEmail,
        );
    }
}
