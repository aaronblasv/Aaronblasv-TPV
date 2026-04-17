<?php

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\UpdateTable\UpdateTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateTableController
{
    public function __construct(
        private UpdateTable $useCase,
    ) {}

    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => 'required|uuid',
        ]);

        $table = ($this->useCase)(
            $uuid,
            $validated['name'],
            $validated['zone_id'],
            $request->user()->restaurant_id,
        );

        return new JsonResponse($table->toArray());
    }
}
