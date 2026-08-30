<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El índice único de pagos_alquiler estaba ligado solo a la unidad+mes,
// sin el cliente. Eso impedía generar el pago del nuevo inquilino cuando
// una unidad cambiaba de manos a mitad de mes: el sistema veía que "ya
// existía un pago" para esa unidad ese mes (del inquilino anterior) y no
// creaba uno nuevo. Añadir cliente_id al índice permite que coexistan
// pagos de distintos inquilinos para la misma unidad en el mismo mes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_alquiler', function (Blueprint $table) {
            $table->dropUnique('unique_pago_mes');
            $table->unique(['tipo', 'referencia_id', 'cliente_id', 'mes', 'anyo'], 'unique_pago_mes_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_alquiler', function (Blueprint $table) {
            $table->dropUnique('unique_pago_mes_cliente');
            $table->unique(['tipo', 'referencia_id', 'mes', 'anyo'], 'unique_pago_mes');
        });
    }
};
