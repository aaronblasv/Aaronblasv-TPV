<?php

namespace App\Log\Infrastructure\Entrypoint\Http;

use App\Log\Application\GetLogs\GetLogs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetLogsController
{
    public function __construct(private GetLogs $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $action = $request->query('action');
        $userId = $request->query('user_id');
        $limit = (int) $request->query('limit', 50);
        $offset = (int) $request->query('offset', 0);

        $response = ($this->useCase)($request->user()->restaurant_id, $action, $userId, $limit, $offset);

        return new JsonResponse($response->toArray(), 200);
    }
}
