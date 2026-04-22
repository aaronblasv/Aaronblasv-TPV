<?php

declare(strict_types=1);

namespace App\Payment\Infrastructure\Entrypoint\Http;

use App\Payment\Application\RegisterPayment\RegisterPayment;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterPaymentController
{
    public function __construct(
        private RegisterPayment $useCase,
    ) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'paid_by_user_id' => 'required|uuid',
            'amount' => 'required|integer|min:1',
            'method' => 'required|in:cash,card,bizum',
            'description' => 'nullable|string|max:255',
        ]);

        $response = ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                $request->user()->uuid,
                $request->ip(),
            ),
            $orderUuid,
            $validated['paid_by_user_id'],
            $validated['amount'],
            $validated['method'],
            $validated['description'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
