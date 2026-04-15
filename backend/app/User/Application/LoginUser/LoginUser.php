<?php

namespace App\User\Application\LoginUser;

use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\Interfaces\TokenGeneratorInterface;
use App\User\Application\LoginUser\LoginUserResponse;

class LoginUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenGeneratorInterface $tokenGenerator,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $password) {

        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !$this->passwordHasher->verify($password, $user->passwordHash()->getValue())) {
            throw new \Exception('Invalid credentials');
        }

        $token = $this->tokenGenerator->generateToken($user);

        return LoginUserResponse::create($token, $user);
    }
}