<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar productos a una compra ya registrada:
     *  - cada ampliación entra con su propia entrada de stock, así que un almacén puede recibir varias veces de la misma
     *    compra (se quita el índice único purchase_id + warehouse_id de purchase_receipts).
     *  - cada ampliación pagada desde caja genera su propia transacción: purchase_transactions las enlaza a la compra
     *    (purchases.transaction_id sigue guardando el pago original).
     */
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            // MySQL usa el índice único como respaldo de la clave foránea purchase_id: antes de quitarlo hace falta otro índice.
            $table->index('purchase_id');
            $table->dropUnique(['purchase_id', 'warehouse_id']);
        });

        Schema::create('purchase_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_transactions');

        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->unique(['purchase_id', 'warehouse_id']);
            $table->dropIndex(['purchase_id']);
        });
    }
};
