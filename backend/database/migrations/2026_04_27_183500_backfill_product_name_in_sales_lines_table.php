<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('sales_lines as sl')
            ->join('order_lines as ol', 'ol.id', '=', 'sl.order_line_id')
            ->join('products as p', 'p.id', '=', 'ol.product_id')
            ->where(function ($query) {
                $query->whereNull('sl.product_name')->orWhere('sl.product_name', '');
            })
            ->get(['sl.id', 'p.name']);

        foreach ($rows as $row) {
            DB::table('sales_lines')
                ->where('id', $row->id)
                ->update(['product_name' => $row->name]);
        }
    }

    public function down(): void
    {
    }
};
