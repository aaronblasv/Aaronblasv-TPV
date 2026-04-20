<?php

declare(strict_types=1);

namespace App\CashShift\Infrastructure\Entrypoint\Http;

use App\CashShift\Application\CloseCashShift\CloseCashShift;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloseCashShiftController
{
    public function __construct(
        private CloseCashShift $useCase,
    ) {}

    public function __invoke(Request $request, string $cashShiftUuid): JsonResponse
    {
        $validated = $request->validate([
            'counted_cash' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $response = ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                $request->user()->uuid,
                $request->ip(),
            ),
            $cashShiftUuid,
            $validated['counted_cash'],
            $validated['notes'] ?? null,
        );

        return new JsonResponse($response->toArray(), 200);
    }
}