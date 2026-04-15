<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\CloseOrder\CloseOrder;
use App\Log\Application\CreateLog\CreateLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloseOrderController
{
    public function __construct(
        private CloseOrder $useCase,
        private CreateLog $createLog,
    ) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'closed_by_user_id' => 'required|string',
        ]);

        $response = ($this->useCase)(
            $orderUuid,
            $validated['closed_by_user_id'],
            $request->user()->restaurant_id,
        );

        ($this->createLog)(
            $request->user()->restaurant_id,
            $request->user()->uuid,
            'order.closed',
            'order',
            $orderUuid,
            ['closed_by_user_id' => $validated['closed_by_user_id']],
            $request->ip(),
        );

        return new JsonResponse($response->toArray());
    }
}
