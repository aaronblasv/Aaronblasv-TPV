<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\DeactivateFamily\DeactivateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeactivateFamilyController
{
    public function __construct(
        private DeactivateFamily $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        ($this->useCase)($uuid, $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
