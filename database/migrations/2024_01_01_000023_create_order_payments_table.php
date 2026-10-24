<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada cobro de una venta: el pago al contado, el anticipo de una venta a crédito o un abono
     * posterior. El dinero en sí vive en `transactions`; aquí se enlaza con la venta.
     */
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->string('receipt_photo')->nullable(); // comprobante de este cobro
            $table->boolean('canceled')->default(false);
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};