<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\DeleteTable\DeleteTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteTableController
{
    public function __construct(
        private DeleteTable $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        ($this->useCase)($uuid, $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
