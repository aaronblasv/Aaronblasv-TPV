<?php

namespace App\Payment\Infrastructure\Entrypoint\Http;

use App\Payment\Application\RegisterPayment\RegisterPayment;
use App\Log\Application\CreateLog\CreateLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterPaymentController
{
    public function __construct(
        private RegisterPayment $useCase,
        private CreateLog $createLog,
    ) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|in:cash,card,bizum',
            'description' => 'nullable|string|max:255',
        ]);

        $response = ($this->useCase)(
            $orderUuid,
            $request->user()->uuid,
            $validated['amount'],
            $validated['method'],
            $validated['description'] ?? null,
        );

        ($this->createLog)(
            $request->user()->restaurant_id,
            $request->user()->uuid,
            'payment.registered',
            'order',
            $orderUuid,
            [
                'amount'     => $validated['amount'],
                'method'     => $validated['method'],
                'total_paid' => $response->totalPaid,
            ],
            $request->ip(),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
