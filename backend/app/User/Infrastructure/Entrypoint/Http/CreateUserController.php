<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\CreateUser\CreateUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateUserController
{
    public function __construct(
        private CreateUser $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|in:admin,supervisor,waiter',
        ]);
        $user = ($this->useCase)(
            $validated['email'],
            $validated['name'],
            $validated['password'],
            $validated['role'] ?? 'waiter',
            auth()->user()->restaurant_id,
        );
        return new JsonResponse($user, 201);
    }
}