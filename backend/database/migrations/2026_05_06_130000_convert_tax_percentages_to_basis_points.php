<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('taxes')->update([
            'percentage' => DB::raw('percentage * 100'),
        ]);

        DB::table('order_lines')->update([
            'tax_percentage' => DB::raw('tax_percentage * 100'),
        ]);

        DB::table('sales_lines')->update([
            'tax_percentage' => DB::raw('tax_percentage * 100'),
        ]);

        DB::table('order_service_window_lines')->update([
            'tax_percentage' => DB::raw('tax_percentage * 100'),
        ]);
    }

    public function down(): void
    {
        DB::table('taxes')->update([
            'percentage' => DB::raw('ROUND(percentage / 100)'),
        ]);

        DB::table('order_lines')->update([
            'tax_percentage' => DB::raw('ROUND(tax_percentage / 100)'),
        ]);

        DB::table('sales_lines')->update([
            'tax_percentage' => DB::raw('ROUND(tax_percentage / 100)'),
        ]);

        DB::table('order_service_window_lines')->update([
            'tax_percentage' => DB::raw('ROUND(tax_percentage / 100)'),
        ]);
    }
};
