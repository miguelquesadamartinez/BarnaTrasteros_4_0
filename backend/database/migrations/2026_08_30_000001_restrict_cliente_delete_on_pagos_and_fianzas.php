<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Antes, borrar un cliente arrastraba en cascada (cascadeOnDelete) todos sus
// pagos de alquiler y fianzas, incluida deuda pendiente y fianzas activas,
// sin ningún aviso. ClienteController::destroy() ahora bloquea el borrado a
// nivel de aplicación si el cliente tiene pagos o fianzas; este cambio de
// clave foránea es la red de seguridad a nivel de base de datos por si esa
// comprobación se salta (p. ej. llamando a la API directamente).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_alquiler', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
        });

        Schema::table('fianzas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos_alquiler', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
        });

        Schema::table('fianzas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
        });
    }
};
