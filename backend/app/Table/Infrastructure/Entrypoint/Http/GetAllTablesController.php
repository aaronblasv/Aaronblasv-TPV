<?php

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\GetAllTables\GetAllTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllTablesController
{
    public function __construct(
        private GetAllTables $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->useCase)($request->user()->restaurant_id);

        return new JsonResponse(array_map(
            static fn($item) => $item->toArray(),
            $response,
        ));
    }
}
