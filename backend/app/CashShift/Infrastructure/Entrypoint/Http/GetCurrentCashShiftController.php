<?php

declare(strict_types=1);

namespace App\CashShift\Infrastructure\Entrypoint\Http;

use App\CashShift\Application\GetCurrentCashShift\GetCurrentCashShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCurrentCashShiftController
{
    public function __construct(private GetCurrentCashShift $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->useCase)($request->user()->restaurant_id);

        return $response
            ? new JsonResponse($response->toArray())
            : new JsonResponse(null, 204);
    }
}