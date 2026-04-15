<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Entrypoint\Http;

use App\Dashboard\Application\GetDashboardStats\GetDashboardStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(
        private GetDashboardStats $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->useCase)($request->user()->restaurant_id);

        return new JsonResponse($response->toArray());
    }
}
