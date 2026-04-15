<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\UpdateUser\UpdateUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateUserController
{
    public function __construct(
        private UpdateUser $updateUser,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($uuid, 'uuid')
            ],
        ]);

        try {
            $response = ($this->updateUser)(
                $uuid,
                $validated['email'],
                $validated['name'],
                $request->user()->restaurant_id,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }

        return new JsonResponse($response->toArray());
    }
}