<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_alquiler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('tipo', ['trastero', 'piso']);
            $table->unsignedBigInteger('referencia_id'); // ID del trastero o piso
            $table->tinyInteger('mes'); // 1-12
            $table->year('anyo');
            $table->decimal('importe_total', 8, 2);
            $table->decimal('pagado', 8, 2)->default(0);
            $table->enum('estado', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índice único para evitar duplicados de mes/año/referencia
            $table->unique(['tipo', 'referencia_id', 'mes', 'anyo'], 'unique_pago_mes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_alquiler');
    }
};
