<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetAllUsers\GetAllUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllUsersController
{
    public function __construct(
        private GetAllUsers $getAllUsers,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->getAllUsers)($request->user()->restaurant_id);

        return new JsonResponse(array_map(
            static fn($item) => $item->toArray(),
            $response,
        ));
    }
}