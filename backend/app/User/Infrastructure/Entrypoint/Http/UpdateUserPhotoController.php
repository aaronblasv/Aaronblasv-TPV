<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\UpdateUser\UpdateUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateUserPhotoController
{
    public function __construct(
        private UpdateUser $updateUser,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'image_src' => 'nullable|string',
        ]);

        // Get the user's data first to preserve name/email
        $user = \App\User\Infrastructure\Persistence\Models\EloquentUser::where('uuid', $uuid)->firstOrFail();

        $response = ($this->updateUser)(
            $uuid,
            $user->email,
            $user->name,
            $user->restaurant_id,
            $validated['image_src'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
