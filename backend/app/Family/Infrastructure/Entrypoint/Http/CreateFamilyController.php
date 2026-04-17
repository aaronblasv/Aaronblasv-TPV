<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateFamilyController
{
    public function __construct(
        private CreateFamily $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $family = ($this->useCase)(
            $validated['name'],
            $request->boolean('active', true),
            $request->user()->restaurant_id,
        );

        return new JsonResponse($family->toArray(), 201);
    }
}
