<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderByTable\GetOrderByTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetOrderByTableController
{
    public function __construct(private GetOrderByTable $useCase) {}

    public function __invoke(Request $request, string $tableUuid): JsonResponse
    {
        $response = ($this->useCase)($tableUuid, $request->user()->restaurant_id);

        if (!$response) {
            return new JsonResponse(null, 204);
        }

        return new JsonResponse($response->toArray());
    }
}
