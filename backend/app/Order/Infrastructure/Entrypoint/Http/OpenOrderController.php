<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\OpenOrder\OpenOrder;
use App\Log\Application\CreateLog\CreateLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenOrderController
{
    public function __construct(
        private OpenOrder $useCase,
        private CreateLog $createLog,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => 'required|string',
            'opened_by_user_id' => 'required|string',
            'diners' => 'required|integer|min:1',
        ]);

        $response = ($this->useCase)(
            $request->user()->restaurant_id,
            $validated['table_id'],
            $validated['opened_by_user_id'],
            $validated['diners'],
        );

        // Log the action
        ($this->createLog)(
            $request->user()->restaurant_id,
            $request->user()->uuid,
            'order.opened',
            'order',
            $response->uuid,
            [
                'table_id' => $validated['table_id'],
                'diners' => $validated['diners'],
                'opened_by_user_id' => $validated['opened_by_user_id'],
            ],
            $request->ip(),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}