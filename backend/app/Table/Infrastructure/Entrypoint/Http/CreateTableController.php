<?php

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\CreateTable\CreateTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateTableController
{
    public function __construct(
        private CreateTable $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => 'required|uuid',
        ]);

        $table = ($this->useCase)(
            $validated['name'],
            $validated['zone_id'],
            $request->user()->restaurant_id,
        );

        return new JsonResponse($table->toArray(), 201);
    }
}
