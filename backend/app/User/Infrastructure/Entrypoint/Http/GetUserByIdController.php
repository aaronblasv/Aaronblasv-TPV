<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetUserById\GetUserById;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetUserByIdController
{
    public function __construct(
        private GetUserById $getUserById,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $response = ($this->getUserById)($uuid);

        return new JsonResponse($response->toArray());
    }
}