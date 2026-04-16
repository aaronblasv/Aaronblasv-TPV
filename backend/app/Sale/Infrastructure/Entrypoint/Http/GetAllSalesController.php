<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetAllSales\GetAllSales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllSalesController
{
    public function __construct(
        private GetAllSales $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $sales = ($this->useCase)(
            $request->user()->restaurant_id,
            is_string($from) ? $from : null,
            is_string($to)   ? $to   : null,
        );

        return new JsonResponse($sales);
    }
}
