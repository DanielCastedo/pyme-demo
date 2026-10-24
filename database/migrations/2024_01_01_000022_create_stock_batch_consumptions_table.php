<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batch_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_batch_id')->constrained('stock_batches')->onDelete('cascade');
            $table->foreignId('stock_movement_item_id')->nullable()->constrained('stock_movement_items')->onDelete('cascade');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batch_consumptions');
    }
};
