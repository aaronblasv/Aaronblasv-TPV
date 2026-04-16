<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\DeleteUser\DeleteUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteUserController
{
    public function __construct(
        private DeleteUser $deleteUser,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        ($this->deleteUser)($uuid, $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
