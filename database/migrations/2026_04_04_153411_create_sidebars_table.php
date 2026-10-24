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
        Schema::create('sidebars', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: 'Usuarios'
            $table->string('ruta')->nullable(); // Nombre de la ruta, Ej: 'usuarios.index'
            $table->text('icono')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // Para hacer submenús (Ej: Semestre 1)
            $table->integer('orden')->default(0); // Para ordenar el menú

            // Control de acceso (Opcional, si es null, todos lo ven)
            $table->string('rol_requerido')->nullable();
            $table->string('permiso_requerido')->nullable();

            $table->timestamps();

            // Llave foránea para submenús
            $table->foreign('parent_id')->references('id')->on('sidebars')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidebars');
    }
};
