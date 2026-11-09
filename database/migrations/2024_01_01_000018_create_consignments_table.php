<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consignación de salida: mercadería que sale del almacén hacia un cliente sin cobrarse. Más tarde el cliente
     * la liquida: lo vendido se convierte en una venta y lo que devuelve vuelve al stock (ver consignment_settlements).
     *
     * Va antes que stock_movements: cada movimiento de despacho o devolución apunta a su consignación
     * (stock_movements.consignment_id), y una consignación puede tener varios, uno por almacén.
     */
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('status', 15)->default('open');
            $table->text('note')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('branche_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->decimal('quantity_sent', 10, 2);
            $table->decimal('quantity_sold', 10, 2)->default(0);
            $table->decimal('quantity_returned', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0); // descuento acordado sobre toda la cantidad entregada
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0); // costo FIFO por unidad base al despachar
            $table->foreignId('consignment_id')->constrained('consignments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained('product_units')->restrictOnDelete();
            // Almacén del que sale ESTA línea (una consignación puede despachar de varios almacenes).
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_items');
        Schema::dropIfExists('consignments');
    }
};
