<?php

declare(strict_types=1);

namespace App\Refund\Infrastructure\Entrypoint\Http;

use App\Refund\Application\CreateRefund\CreateRefund;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateRefundController
{
    public function __construct(
        private CreateRefund $useCase,
    ) {}

    public function __invoke(Request $request, string $saleUuid): JsonResponse
    {
        $validated = $request->validate([
            'method' => 'required|string',
            'reason' => 'nullable|string|max:255',
            'refund_all' => 'required|boolean',
            'lines' => 'array',
            'lines.*.sale_line_uuid' => 'required_with:lines|string',
            'lines.*.quantity' => 'required_with:lines|integer|min:1',
        ]);

        $response = ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                $request->user()->uuid,
                $request->ip(),
            ),
            $saleUuid,
            $validated['method'],
            $validated['reason'] ?? null,
            (bool) $validated['refund_all'],
            $validated['lines'] ?? [],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}