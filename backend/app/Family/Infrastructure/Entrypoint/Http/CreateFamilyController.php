<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use App\Family\Application\CreateFamily\CreateFamilyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateFamilyController
{
    public function __construct(
        private CreateFamily $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $active = $request->input('active', true);

        $family = ($this->useCase)($name, $active);

        return new JsonResponse($family);
    }
}