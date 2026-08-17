<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_precios', function (Blueprint $table) {
            $table->decimal('porcentaje', 6, 2)->nullable()->after('precio_nuevo');
        });
    }

    public function down(): void
    {
        Schema::table('historial_precios', function (Blueprint $table) {
            $table->dropColumn('porcentaje');
        });
    }
};
