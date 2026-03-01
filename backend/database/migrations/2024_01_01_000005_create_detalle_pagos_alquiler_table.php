<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pagos_alquiler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_alquiler_id')->constrained('pagos_alquiler')->cascadeOnDelete();
            $table->decimal('importe', 8, 2);
            $table->date('fecha_pago');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pagos_alquiler');
    }
};
