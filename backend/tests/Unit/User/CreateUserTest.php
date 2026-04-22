<?php

namespace Tests\Unit\User;

use App\User\Application\CreateUser\CreateUser;
use App\User\Application\CreateUser\CreateUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\PinGeneratorInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\Pin;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_user_saves_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);
        $pinGenerator = Mockery::mock(PinGeneratorInterface::class);

        $hashedPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $passwordHasher->shouldReceive('hash')
            ->once()
            ->with('plain-password')
            ->andReturn($hashedPassword);

        $pinGenerator->shouldReceive('generate')
            ->once()
            ->andReturn(Pin::create('1234'));

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (User $user) use ($hashedPassword) {
                return $user->email()->getValue() === 'create@example.com'
                    && $user->name()->getValue() === 'Create User'
                    && $user->passwordHash()->getValue() === $hashedPassword
                    && $user->role()->value === 'waiter'
                    && $user->restaurantId()->getValue() === 1
                    && $user->pin()?->getValue() === '1234';
            }));

        $createUser = new CreateUser($repository, $passwordHasher, $pinGenerator);
        $response = $createUser('create@example.com', 'Create User', 'plain-password', 'waiter', 1);

        $this->assertInstanceOf(CreateUserResponse::class, $response);
        $this->assertSame('create@example.com', $response->email);
        $this->assertSame('Create User', $response->name);
        $this->assertSame('1234', $response->pin);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->id
        );
        $array = $response->toArray();
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
