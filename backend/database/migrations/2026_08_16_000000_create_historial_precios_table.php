<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_precios', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['trastero', 'piso']);
            $table->unsignedBigInteger('referencia_id');
            $table->string('numero', 20)->nullable();
            $table->decimal('precio_anterior', 8, 2);
            $table->decimal('precio_nuevo', 8, 2);
            $table->string('motivo', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_precios');
    }
};
