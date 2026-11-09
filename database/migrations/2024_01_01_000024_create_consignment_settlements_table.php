<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liquidación de una consignación (ver create_consignments_table): lo que el cliente vendió se convierte en una
     * venta (order_id) y lo que devuelve vuelve al stock con un movimiento de entrada (return_movement_id).
     */
    public function up(): void
    {
        Schema::create('consignment_settlements', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->text('note')->nullable();
            $table->decimal('sold_amount', 12, 2)->default(0);
            $table->foreignId('consignment_id')->constrained('consignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('return_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consignment_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->decimal('quantity_sold', 10, 2)->default(0);
            $table->decimal('quantity_returned', 10, 2)->default(0);
            $table->foreignId('consignment_settlement_id')->constrained('consignment_settlements')->cascadeOnDelete();
            $table->foreignId('consignment_item_id')->constrained('consignment_items')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_settlement_items');
        Schema::dropIfExists('consignment_settlements');
    }
};
