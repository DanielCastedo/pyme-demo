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
            $table->string('lot_number', 50)->nullable(); // lote del fabricante
            $table->date('expires_at')->nullable(); // la salida gasta primero lo que vence antes (FEFO)
            $table->dateTime('received_at');
            // 4 decimales: al comprar por caja y vender por unidad, el costo por unidad suele tener más de 2
            // (caja de 30 a Bs 25 = Bs 0.8333 c/u).
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('qty_initial', 12, 2);
            $table->decimal('qty_remaining', 12, 2);
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'received_at']);
            $table->index(['product_id', 'warehouse_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
