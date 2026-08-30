<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// La fecha de vencimiento marca el día del mes en que se considera atrasado
// el pago de una unidad. Por defecto es un mes después de la fecha de inicio
// de alquiler, pero se puede alterar manualmente desde la ficha del cliente
// o del propio trastero/piso.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trasteros', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('fecha_inicio_alquiler');
        });

        Schema::table('pisos', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('fecha_inicio_alquiler');
        });

        // Backfill: unidades ya alquiladas -> vencimiento = inicio + 1 mes.
        foreach (['trasteros', 'pisos'] as $tabla) {
            DB::table($tabla)
                ->whereNotNull('cliente_id')
                ->whereNotNull('fecha_inicio_alquiler')
                ->orderBy('id')
                ->get(['id', 'fecha_inicio_alquiler'])
                ->each(function ($fila) use ($tabla) {
                    DB::table($tabla)->where('id', $fila->id)->update([
                        'fecha_vencimiento' => \Carbon\Carbon::parse($fila->fecha_inicio_alquiler)->addMonth()->toDateString(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('trasteros', function (Blueprint $table) {
            $table->dropColumn('fecha_vencimiento');
        });

        Schema::table('pisos', function (Blueprint $table) {
            $table->dropColumn('fecha_vencimiento');
        });
    }
};
