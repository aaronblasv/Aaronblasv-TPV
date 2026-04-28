<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->string('product_name')->after('order_line_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });
    }
};
