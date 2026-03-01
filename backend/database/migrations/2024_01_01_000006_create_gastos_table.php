<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['agua', 'luz', 'comunidad', 'mantenimiento', 'otro']);
            $table->string('descripcion', 200);
            $table->enum('referencia_tipo', ['piso', 'trastero', 'general'])->default('general');
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('importe_total', 8, 2);
            $table->decimal('pagado', 8, 2)->default(0);
            $table->enum('estado', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
