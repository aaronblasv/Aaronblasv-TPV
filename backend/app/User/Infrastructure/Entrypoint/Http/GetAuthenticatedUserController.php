<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetAuthenticatedUser\GetAuthenticatedUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAuthenticatedUserController
{
    public function __construct(
        private GetAuthenticatedUser $getAuthenticatedUser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $response = ($this->getAuthenticatedUser)($user->uuid);

        return new JsonResponse($response->toArray());
    }
}