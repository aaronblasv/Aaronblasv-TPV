<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\SendOrderToKitchen\SendOrderToKitchen;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendOrderToKitchenController
{
    public function __construct(private SendOrderToKitchen $useCase) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $sentByUserId = $request->input('sent_by_user_id');

        ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                is_string($sentByUserId) && $sentByUserId !== '' ? $sentByUserId : $request->user()->uuid,
                $request->ip(),
            ),
            $orderUuid,
        );

        return new JsonResponse(null, 204);
    }
}