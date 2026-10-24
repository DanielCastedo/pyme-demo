<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movement_items', function (Blueprint $table) {
            $table->id();
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->decimal('quantity', 10, 2);
            $table->decimal('base_quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_unit_id')->constrained('product_units')->onDelete('cascade');
            // Si la salida es una parte de un kit vendido, a qué kit (y presentación) pertenece: agrupa costo y ganancia bajo el kit.
            $table->foreignId('kit_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('kit_product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movement_items');
    }
};
