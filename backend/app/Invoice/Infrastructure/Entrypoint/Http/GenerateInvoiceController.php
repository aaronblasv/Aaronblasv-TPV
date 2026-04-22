<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Entrypoint\Http;

use App\Invoice\Application\GenerateInvoice\GenerateInvoice;
use App\Shared\Application\Context\AuditContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateInvoiceController
{
    public function __construct(
        private GenerateInvoice $useCase,
    ) {}

    public function __invoke(Request $request, string $orderUuid): JsonResponse
    {
        $validated = $request->validate([
            'issued_by_user_id' => 'required|uuid',
        ]);

        $response = ($this->useCase)(
            new AuditContext(
                $request->user()->restaurant_id,
                $request->user()->uuid,
                $request->ip(),
            ),
            $orderUuid,
            $validated['issued_by_user_id'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
