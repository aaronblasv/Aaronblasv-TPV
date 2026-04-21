<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\ActivateProduct\ActivateProduct;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivateProductController
{
    public function __construct(
        private ActivateProduct $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                $request->user()->uuid,
                $request->ip(),
            ),
            $uuid,
        );

        return new JsonResponse(null, 204);
    }
}
