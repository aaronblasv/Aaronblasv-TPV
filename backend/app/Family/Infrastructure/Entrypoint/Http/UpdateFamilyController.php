<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\UpdateFamily\UpdateFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateFamilyController
{
    public function __construct(
        private UpdateFamily $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        $family = ($this->useCase)(
            $uuid,
            $validated['name'],
            $validated['active'],
            $request->user()->restaurant_id,
        );

        return new JsonResponse($family->toArray());
    }
}
