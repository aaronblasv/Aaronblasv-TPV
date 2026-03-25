<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\UpdateFamily\UpdateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateFamilyController
{
    public function __construct(
        private UpdateFamily $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $family = ($this->useCase)($uuid, $request->input('name'), $request->input('active'));

        return new JsonResponse($family);
    }
}