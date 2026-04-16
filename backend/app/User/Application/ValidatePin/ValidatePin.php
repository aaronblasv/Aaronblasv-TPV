<?php

declare(strict_types=1);

namespace App\User\Application\ValidatePin;

use App\User\Domain\Exception\InvalidPinException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class ValidatePin
{
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {}

    public function __invoke(string $pin, int $restaurantId): ValidatePinResponse
    {
        $user = $this->repository->findByPin($pin, $restaurantId);
        if (!$user) {
            throw new InvalidPinException();
        }

        return ValidatePinResponse::create($user);
    }
}