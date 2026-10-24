<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('stock_movement_item_id')->nullable()->constrained('stock_movement_items')->nullOnDelete();
            $table->dateTime('received_at');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('qty_initial', 12, 2);
            $table->decimal('qty_remaining', 12, 2);
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
