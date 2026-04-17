<?php

declare(strict_types=1);

namespace App\Table\Infrastructure\Entrypoint\Http;

use App\Table\Application\MergeTables\MergeTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MergeTablesController
{
    public function __construct(private MergeTables $useCase) {}

    public function __invoke(Request $request, string $tableUuid): JsonResponse
    {
        $validated = $request->validate([
            'table_uuids' => 'required|array|min:1',
            'table_uuids.*' => 'required|string',
        ]);

        ($this->useCase)($tableUuid, $validated['table_uuids'], $request->user()->restaurant_id);

        return new JsonResponse(null, 204);
    }
}
