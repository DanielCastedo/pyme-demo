<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compras de mercadería: cada compra entra al inventario como un lote FIFO con su costo (a través de un
     * movimiento de entrada) y, si se paga en el momento, sale dinero de una caja (transacción tipo Compra).
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type', 10)->default('nit');
            $table->string('document', 50)->nullable()->unique();
            $table->string('contact_name')->nullable(); // persona de contacto
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->string('invoice_number', 100)->nullable();
            $table->text('note')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('received'); // received | canceled
            $table->string('payment_type', 10)->default('cash'); // cash | credit
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('branche_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained('product_units')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 10, 2); // costo por presentación comprada (caja, unidad...)
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // Cada pago de una compra a crédito: el anticipo o un abono posterior. El dinero en sí vive en
        // `transactions`; aquí se enlaza con la compra (igual que order_payments con las ventas).
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->boolean('canceled')->default(false);
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
