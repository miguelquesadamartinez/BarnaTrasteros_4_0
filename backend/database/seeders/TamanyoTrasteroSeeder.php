<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TamanyoTrasteroSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tamanyo_trasteros')->truncate();

        DB::table('tamanyo_trasteros')->insert([
            ['nombre' => 'Pequeño (5m²)',  'descripcion' => 'Hasta 5 m²',       'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mediano (10m²)', 'descripcion' => 'Entre 5 y 10 m²',  'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Grande (20m²)',  'descripcion' => 'Entre 10 y 20 m²', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Extra (30m²)',   'descripcion' => 'Más de 20 m²',     'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅ Tamaños de trastero creados.');
    }
}
