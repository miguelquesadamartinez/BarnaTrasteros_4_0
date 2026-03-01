<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('direccion', 200)->nullable()->after('foto_dni');
            $table->string('codigo_postal', 10)->nullable()->after('direccion');
            $table->string('ciudad', 100)->nullable()->after('codigo_postal');
            $table->boolean('necesita_factura')->default(false)->after('ciudad');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'codigo_postal', 'ciudad', 'necesita_factura']);
        });
    }
};
