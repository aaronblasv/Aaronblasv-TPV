<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetSalesReport\GetSalesReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSalesReportController
{
    public function __construct(
        private GetSalesReport $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $report = ($this->useCase)(
            $request->user()->restaurant_id,
            is_string($from) ? $from : null,
            is_string($to)   ? $to   : null,
        );

        return new JsonResponse($report);
    }
}
