<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_service_windows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('sent_by_user_id')->constrained('users');
            $table->string('sent_by_user_name');
            $table->unsignedInteger('window_number');
            $table->timestamp('sent_at');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['order_id', 'window_number']);
        });

        Schema::create('order_service_window_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->foreignId('order_service_window_id')->constrained('order_service_windows');
            $table->foreignId('order_line_id')->constrained('order_lines');
            $table->string('product_name');
            $table->integer('quantity');
            $table->integer('price');
            $table->integer('tax_percentage');
            $table->string('discount_type')->nullable();
            $table->integer('discount_value')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('line_subtotal');
            $table->integer('tax_amount');
            $table->integer('line_total');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_service_window_lines');
        Schema::dropIfExists('order_service_windows');
    }
};
