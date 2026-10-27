<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sucursales que usan cada almacén. Un almacén sigue teniendo una sucursal dueña (warehouses.branche_id),
     * pero puede atender a varias: dos o tres sucursales venden y compran sobre el mismo stock, o cada
     * sucursal tiene el suyo. La sucursal dueña siempre figura aquí también.
     */
    public function up(): void
    {
        Schema::create('branch_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'warehouse_id']);
        });

        // Los almacenes que ya existen quedan atendiendo solo a su sucursal dueña (como funcionaban hasta ahora).
        DB::table('warehouses')->orderBy('id')->each(function ($warehouse) {
            DB::table('branch_warehouse')->insert([
                'branch_id' => $warehouse->branche_id,
                'warehouse_id' => $warehouse->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_warehouse');
    }
};
