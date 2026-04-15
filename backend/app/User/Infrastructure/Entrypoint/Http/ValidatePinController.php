<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\ValidatePin\ValidatePin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidatePinController
{
    public function __construct(private ValidatePin $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => 'required|string',
        ]);

        try {
            $response = ($this->useCase)($validated['pin'], $request->user()->restaurant_id);
            return new JsonResponse($response->toArray());
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        }
    }
}