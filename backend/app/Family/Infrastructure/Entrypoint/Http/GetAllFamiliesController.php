<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\GetAllFamilies\GetAllFamilies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllFamiliesController
{
    public function __construct(
        private GetAllFamilies $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $families = ($this->useCase)();

        return new JsonResponse($families);
    }
}