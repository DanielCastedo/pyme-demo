<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distribución de una compra entre almacenes: cada producto comprado puede repartirse entre varios almacenes
     * (ej. 8 unidades = 5 al almacén A y 3 al B). Cada almacén destino recibe su parte con un movimiento de entrada
     * propio (purchase_receipts), y purchase_item_distributions guarda cuánto de cada producto fue a cada almacén.
     * Las compras anteriores no tienen estas filas: se entiende que todo entró a purchases.warehouse_id.
     */
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->timestamps();
            $table->unique(['purchase_id', 'warehouse_id']);
        });

        Schema::create('purchase_item_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->decimal('quantity', 12, 2); // en la presentación comprada (igual que purchase_items.quantity)
            $table->timestamps();
            $table->unique(['purchase_item_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_item_distributions');
        Schema::dropIfExists('purchase_receipts');
    }
};
