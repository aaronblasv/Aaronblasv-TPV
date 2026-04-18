<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\UpdateUser\UpdateUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateUserController
{
    public function __construct(
        private UpdateUser $updateUser,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($uuid, 'uuid')],
            'image_src' => ['nullable', 'string'],
        ]);

        $response = ($this->updateUser)(
            $uuid,
            $validated['email'],
            $validated['name'],
            $request->user()->restaurant_id,
            $validated['image_src'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
