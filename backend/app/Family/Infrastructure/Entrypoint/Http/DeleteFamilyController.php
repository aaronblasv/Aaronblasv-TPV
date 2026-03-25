<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Family\Application\DeleteFamily\DeleteFamily;

class DeleteFamilyController
{
    public function __construct(
        private DeleteFamily $deleteFamily,
    ) {
    }

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $this->deleteFamily->__invoke($uuid);

        return new JsonResponse(null, 204);
    }
}