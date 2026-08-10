<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fianzas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('tipo', ['trastero', 'piso'])->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable(); // ID del trastero o piso
            $table->string('numero', 20)->nullable();                // Número del trastero o piso
            $table->decimal('importe', 8, 2);
            $table->date('fecha_entrega');
            $table->boolean('devuelta')->default(false);
            $table->date('fecha_devolucion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fianzas');
    }
};
