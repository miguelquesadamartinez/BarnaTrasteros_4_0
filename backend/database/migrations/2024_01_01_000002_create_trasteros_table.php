<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trasteros', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->string('piso', 20);
            $table->string('tamanyo', 50); // ej: "pequeño", "mediano", "grande" o metros
            $table->decimal('precio_mensual', 8, 2)->default(0);
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->date('fecha_inicio_alquiler')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trasteros');
    }
};
