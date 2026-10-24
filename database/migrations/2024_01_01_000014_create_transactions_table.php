<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->dateTime('date');
            $table->decimal('amount', 12, 2);
            $table->boolean('canceled')->default(false);
            $table->text('memo')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('cashbox_id')->constrained('cashboxes')->onDelete('cascade');
            $table->foreignId('branche_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('related_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
            $table->index(['cashbox_id', 'type_id', 'canceled'], 'transactions_cashbox_type_canceled_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
