<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar restricciones de clave foránea durante el seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('detalle_pagos_alquiler')->truncate();
        DB::table('pagos_alquiler')->truncate();
        DB::table('imagenes_gastos')->truncate();
        DB::table('detalle_gastos')->truncate();
        DB::table('gastos')->truncate();
        DB::table('trasteros')->truncate();
        DB::table('pisos')->truncate();
        DB::table('clientes')->truncate();
        DB::table('tamanyo_trasteros')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ============================================================
        // TAMAÑOS DE TRASTEROS
        // ============================================================
        DB::table('tamanyo_trasteros')->insert([
            ['nombre' => 'Pequeño (5m²)',   'descripcion' => 'Hasta 5 m²',          'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mediano (10m²)',  'descripcion' => 'Entre 5 y 10 m²',     'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Grande (20m²)',   'descripcion' => 'Entre 10 y 20 m²',    'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Extra (30m²)',    'descripcion' => 'Más de 20 m²',        'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ============================================================
        // CLIENTES
        // ============================================================
        DB::table('clientes')->insert([
            ['id' => 1, 'nombre' => 'María',  'apellido' => 'García López',     'telefono' => '612345678', 'dni' => '12345678A', 'foto_dni' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'nombre' => 'Juan',   'apellido' => 'Martínez Ruiz',    'telefono' => '623456789', 'dni' => '23456789B', 'foto_dni' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'nombre' => 'Ana',    'apellido' => 'Fernández Torres', 'telefono' => '634567890', 'dni' => '34567890C', 'foto_dni' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'nombre' => 'Pedro',  'apellido' => 'Sánchez Morales',  'telefono' => '645678901', 'dni' => '45678901D', 'foto_dni' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 5, 'nombre' => 'Laura',  'apellido' => 'López Jiménez',    'telefono' => '656789012', 'dni' => '56789012E', 'foto_dni' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // ============================================================
        // TRASTEROS
        // ============================================================
        DB::table('trasteros')->insert([
            ['id' => 1, 'numero' => 'T-01', 'piso' => 'Planta Baja', 'tamanyo' => 'Pequeño (5m²)',  'precio_mensual' => 60.00,  'cliente_id' => 1, 'fecha_inicio_alquiler' => '2024-01-01', 'notas' => 'Trastero esquina', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'numero' => 'T-02', 'piso' => 'Planta Baja', 'tamanyo' => 'Mediano (10m²)', 'precio_mensual' => 90.00,  'cliente_id' => 2, 'fecha_inicio_alquiler' => '2024-03-01', 'notas' => null,              'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'numero' => 'T-03', 'piso' => 'Sótano',      'tamanyo' => 'Grande (20m²)',  'precio_mensual' => 150.00, 'cliente_id' => null, 'fecha_inicio_alquiler' => null,       'notas' => 'Disponible',       'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'numero' => 'T-04', 'piso' => 'Sótano',      'tamanyo' => 'Pequeño (5m²)',  'precio_mensual' => 60.00,  'cliente_id' => 3, 'fecha_inicio_alquiler' => '2023-06-01', 'notas' => null,              'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 5, 'numero' => 'T-05', 'piso' => 'Planta Baja', 'tamanyo' => 'Mediano (10m²)', 'precio_mensual' => 90.00,  'cliente_id' => null, 'fecha_inicio_alquiler' => null,       'notas' => 'Disponible',       'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // ============================================================
        // PISOS
        // ============================================================
        DB::table('pisos')->insert([
            ['id' => 1, 'numero' => 'P-1A', 'piso' => '1º', 'precio_mensual' => 800.00, 'cliente_id' => 4, 'fecha_inicio_alquiler' => '2023-09-01', 'notas' => 'Piso exterior, 3 habitaciones', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'numero' => 'P-1B', 'piso' => '1º', 'precio_mensual' => 750.00, 'cliente_id' => 5, 'fecha_inicio_alquiler' => '2024-01-01', 'notas' => 'Piso interior, 2 habitaciones', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'numero' => 'P-2A', 'piso' => '2º', 'precio_mensual' => 850.00, 'cliente_id' => null, 'fecha_inicio_alquiler' => null, 'notas' => 'Disponible, recién reformado', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // ============================================================
        // PAGOS DE ALQUILER (3 meses)
        // ============================================================
        $meses = [];
        for ($i = 2; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $meses[] = ['mes' => $fecha->month, 'anyo' => $fecha->year];
        }

        $pagos = [];
        $pagoId = 1;

        // T-01 → cliente 1: pagados los 2 primeros, pendiente el último
        foreach ($meses as $idx => $m) {
            $pagos[] = ['id' => $pagoId++, 'cliente_id' => 1, 'tipo' => 'trastero', 'referencia_id' => 1, 'mes' => $m['mes'], 'anyo' => $m['anyo'], 'importe_total' => 60.00,  'pagado' => ($idx < 2) ? 60.00 : 0.00,  'estado' => ($idx < 2) ? 'pagado' : 'pendiente', 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }
        // T-02 → cliente 2: primero pagado, segundo parcial, tercero pendiente
        foreach ($meses as $idx => $m) {
            $pagado = ($idx === 0) ? 90.00 : ($idx === 1 ? 45.00 : 0.00);
            $estado = ($idx === 0) ? 'pagado' : ($idx === 1 ? 'parcial' : 'pendiente');
            $pagos[] = ['id' => $pagoId++, 'cliente_id' => 2, 'tipo' => 'trastero', 'referencia_id' => 2, 'mes' => $m['mes'], 'anyo' => $m['anyo'], 'importe_total' => 90.00,  'pagado' => $pagado, 'estado' => $estado, 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }
        // T-04 → cliente 3: todos pagados
        foreach ($meses as $idx => $m) {
            $pagos[] = ['id' => $pagoId++, 'cliente_id' => 3, 'tipo' => 'trastero', 'referencia_id' => 4, 'mes' => $m['mes'], 'anyo' => $m['anyo'], 'importe_total' => 60.00,  'pagado' => 60.00, 'estado' => 'pagado', 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }
        // P-1A → cliente 4: últimos pagados, actual pendiente
        foreach ($meses as $idx => $m) {
            $pagos[] = ['id' => $pagoId++, 'cliente_id' => 4, 'tipo' => 'piso', 'referencia_id' => 1, 'mes' => $m['mes'], 'anyo' => $m['anyo'], 'importe_total' => 800.00, 'pagado' => ($idx < 2) ? 800.00 : 0.00, 'estado' => ($idx < 2) ? 'pagado' : 'pendiente', 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }
        // P-1B → cliente 5: primero pagado, resto pendientes
        foreach ($meses as $idx => $m) {
            $pagos[] = ['id' => $pagoId++, 'cliente_id' => 5, 'tipo' => 'piso', 'referencia_id' => 2, 'mes' => $m['mes'], 'anyo' => $m['anyo'], 'importe_total' => 750.00, 'pagado' => ($idx === 0) ? 750.00 : 0.00, 'estado' => ($idx === 0) ? 'pagado' : 'pendiente', 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }

        DB::table('pagos_alquiler')->insert($pagos);

        // ============================================================
        // DETALLE PAGOS ALQUILER
        // ============================================================
        $detalles = [];
        $detalleId = 1;
        foreach ($pagos as $p) {
            if ($p['pagado'] > 0) {
                $detalles[] = [
                    'id'               => $detalleId++,
                    'pago_alquiler_id' => $p['id'],
                    'importe'          => $p['pagado'],
                    'fecha_pago'       => Carbon::create($p['anyo'], $p['mes'], 5)->format('Y-m-d'),
                    'notas'            => null,
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now(),
                ];
            }
        }
        DB::table('detalle_pagos_alquiler')->insert($detalles);

        // ============================================================
        // GASTOS
        // ============================================================
        DB::table('gastos')->insert([
            ['id' => 1, 'tipo' => 'agua',         'descripcion' => 'Factura agua Enero 2026',          'referencia_tipo' => 'general', 'referencia_id' => null, 'fecha_emision' => '2026-01-15', 'fecha_vencimiento' => '2026-02-15', 'importe_total' => 120.50, 'pagado' => 120.50, 'estado' => 'pagado',   'notas' => null,                    'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'tipo' => 'luz',          'descripcion' => 'Factura luz Enero 2026',           'referencia_tipo' => 'general', 'referencia_id' => null, 'fecha_emision' => '2026-01-20', 'fecha_vencimiento' => '2026-02-20', 'importe_total' => 235.80, 'pagado' => 235.80, 'estado' => 'pagado',   'notas' => null,                    'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'tipo' => 'agua',         'descripcion' => 'Factura agua Febrero 2026',        'referencia_tipo' => 'general', 'referencia_id' => null, 'fecha_emision' => '2026-02-15', 'fecha_vencimiento' => '2026-03-15', 'importe_total' => 115.30, 'pagado' => 0.00,   'estado' => 'pendiente','notas' => null,                    'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'tipo' => 'luz',          'descripcion' => 'Factura luz Febrero 2026',         'referencia_tipo' => 'general', 'referencia_id' => null, 'fecha_emision' => '2026-02-20', 'fecha_vencimiento' => '2026-03-20', 'importe_total' => 198.60, 'pagado' => 100.00, 'estado' => 'parcial',  'notas' => 'Pendiente pago completo','created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 5, 'tipo' => 'mantenimiento','descripcion' => 'Reparación fontanería piso P-1A',  'referencia_tipo' => 'piso',    'referencia_id' => 1,    'fecha_emision' => '2026-02-10', 'fecha_vencimiento' => '2026-02-28', 'importe_total' => 350.00, 'pagado' => 350.00, 'estado' => 'pagado',   'notas' => 'Factura nº 2026-0123',  'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // ============================================================
        // DETALLE GASTOS
        // ============================================================
        DB::table('detalle_gastos')->insert([
            ['id' => 1, 'gasto_id' => 1, 'importe' => 120.50, 'fecha_pago' => '2026-02-10', 'notas' => null,           'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'gasto_id' => 2, 'importe' => 235.80, 'fecha_pago' => '2026-02-15', 'notas' => null,           'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'gasto_id' => 4, 'importe' => 100.00, 'fecha_pago' => '2026-02-25', 'notas' => 'Pago parcial', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'gasto_id' => 5, 'importe' => 350.00, 'fecha_pago' => '2026-02-12', 'notas' => null,           'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        $this->command->info('✅ Seeder completado correctamente.');
    }
}

