<?php

declare(strict_types=1);

namespace App\CashShift\Infrastructure\Entrypoint\Http;

use App\CashShift\Application\CloseCashShift\CloseCashShift;
use App\Shared\Infrastructure\Http\DispatchesActionLogged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloseCashShiftController
{
    use DispatchesActionLogged;

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
            $request->user()->restaurant_id,
            $cashShiftUuid,
            $request->user()->uuid,
            $validated['counted_cash'],
            $validated['notes'] ?? null,
        );

        $this->logAction(
            $request->user()->restaurant_id,
            $request->user()->uuid,
            'cash_shift.closed',
            'cash_shift',
            $cashShiftUuid,
            $response,
            $request->ip(),
        );

        return new JsonResponse($response);
    }
}