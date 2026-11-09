<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apertura y cierre de caja por usuario (un "turno"). Al abrir se anota el fondo inicial para dar cambio: es
     * efectivo que YA está en la caja (no es una venta ni dinero nuevo), por eso no genera transacción y sigue
     * formando parte de la caja. Cada transacción hecha por ese usuario en esa caja mientras el turno está abierto
     * queda enlazada (transactions.cash_session_id). Al cerrar se compara el efectivo esperado con el contado.
     */
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashbox_id')->constrained('cashboxes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->decimal('opening_amount', 12, 2)->default(0); // fondo inicial para dar cambio
            $table->string('opening_note', 255)->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable(); // fondo + efectivo que entró − efectivo que salió
            $table->decimal('counted_cash', 12, 2)->nullable();  // lo que contó el usuario al cerrar
            $table->decimal('difference', 12, 2)->nullable();    // contado − esperado (+ sobrante, − faltante)
            $table->string('closing_note', 255)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'closed_at']);
            $table->index(['cashbox_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
