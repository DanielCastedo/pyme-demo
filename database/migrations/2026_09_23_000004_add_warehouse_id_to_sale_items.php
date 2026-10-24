<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada línea de una venta/pedido/consignación pasa a elegir su propio almacén (antes era uno solo
     * para toda la operación). Las filas existentes se rellenan con el almacén que ya tenía su orden/
     * consignación, así que ningún dato viejo queda sin almacén.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('product_unit_id')->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('consignment_items', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('product_unit_id')->constrained('warehouses')->nullOnDelete();
        });

        // Subconsulta correlacionada (en vez de UPDATE...JOIN) para que funcione igual en MySQL y en SQLite (tests).
        DB::statement('UPDATE order_items SET warehouse_id = (SELECT warehouse_id FROM orders WHERE orders.id = order_items.order_id)');
        DB::statement('UPDATE consignment_items SET warehouse_id = (SELECT warehouse_id FROM consignments WHERE consignments.id = consignment_items.consignment_id)');

        // Una consignación puede ahora despachar (o recibir una devolución) en más de un almacén, así que
        // sus movimientos de stock se enlazan igual que los de una venta (stock_movements.order_id).
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('consignment_id')->nullable()->after('order_id')->constrained('consignments')->nullOnDelete();
        });

        DB::statement('UPDATE stock_movements SET consignment_id = (SELECT id FROM consignments WHERE consignments.dispatch_movement_id = stock_movements.id)');

        // "dispatch_movement_id" queda sin usar (una consignación ahora puede tener varios movimientos, vía
        // consignment_id) pero no se borra la columna: SQLite (los tests) no soporta borrar claves foráneas
        // sin reconstruir la tabla. No molesta quedarse ahí, solo dejó de leerse/escribirse en el código.
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consignment_id');
        });

        Schema::table('consignment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
