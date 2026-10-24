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
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('unit_id')->nullable()->constrained('units')->restrictOnDelete(); // unidad del catálogo; name es su nombre copiado
            $table->unsignedInteger('factor')->default(1); // cuántas unidades base trae (entero)
            $table->decimal('price', 10, 2);
            $table->boolean('is_base')->default(false);
            $table->string('barcode')->nullable()->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
