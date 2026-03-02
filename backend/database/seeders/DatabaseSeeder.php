<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
            ['nombre' => 'Pequeño (5m²)',  'descripcion' => 'Hasta 5 m²',       'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mediano (10m²)', 'descripcion' => 'Entre 5 y 10 m²',  'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Grande (20m²)',  'descripcion' => 'Entre 10 y 20 m²', 'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Extra (30m²)',   'descripcion' => 'Más de 20 m²',     'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ============================================================
        // 20 CLIENTES
        // ============================================================
        // Columnas: id, nombre, apellido, telefono, dni, direccion, cp, ciudad, necesita_factura
        $clientesData = [
            [1,  'María',    'García López',       '612100001', '11111111A', 'Carrer de Mallorca, 123',   '08013', 'Barcelona',   true],
            [2,  'Juan',     'Martínez Ruiz',      '612100002', '22222222B', 'Avinguda Diagonal, 456',    '08036', 'Barcelona',   true],
            [3,  'Ana',      'Fernández Torres',   '612100003', '33333333C', 'Carrer de Provença, 78',    '08009', 'Barcelona',   false],
            [4,  'Pedro',    'Sánchez Morales',    '612100004', '44444444D', 'Carrer de Balmes, 200',     '08006', 'Barcelona',   true],
            [5,  'Laura',    'López Jiménez',      '612100005', '55555555E', 'Passeig de Gràcia, 55',     '08007', 'Barcelona',   false],
            [6,  'Carlos',   'Rodríguez Blanco',   '612100006', '66666666F', 'Carrer de Muntaner, 88',    '08011', 'Barcelona',   true],
            [7,  'Sofía',    'Gómez Castillo',     '612100007', '77777777G', 'Gran Via de les Corts, 300','08015', 'Barcelona',   false],
            [8,  'Miguel',   'Díaz Herrera',       '612100008', '88888888H', 'Carrer de Còrsega, 412',    '08037', 'Barcelona',   true],
            [9,  'Elena',    'Muñoz Vega',         '612100009', '99999999J', 'Carrer de València, 100',   '08011', 'Barcelona',   false],
            [10, 'Roberto',  'Álvarez Peña',       '612100010', '10101010K', 'Carrer de Rosselló, 230',   '08008', 'Barcelona',   true],
            [11, 'Carmen',   'Romero Iglesias',    '612100011', '11111112L', 'Avinguda del Paral·lel, 14','08001', 'Barcelona',   false],
            [12, 'Javier',   'Navarro Serrano',    '612100012', '12121212M', 'Carrer de Tarragona, 50',   '08015', 'Barcelona',   true],
            [13, 'Lucía',    'Torres Molina',      '612100013', '13131313N', 'Carrer de Sants, 77',       '08014', 'Barcelona',   false],
            [14, 'Antonio',  'Ramos Delgado',      '612100014', '14141414P', 'Carrer de la Marina, 210',  '08005', 'Barcelona',   false],
            [15, 'Cristina', 'Vargas Ortega',      '612100015', '15151515Q', 'Rambla del Poblenou, 30',   '08005', 'Barcelona',   false],
            [16, 'Sergio',   'Mora Campos',        '612100016', '16161616R', null,                        null,    null,          false],
            [17, 'Natalia',  'Reyes Guerrero',     '612100017', '17171717S', null,                        null,    null,          false],
            [18, 'Pablo',    'Giménez Fuentes',    '612100018', '18181818T', null,                        null,    null,          false],
            [19, 'Irene',    'Castro Pedraza',     '612100019', '19191919V', null,                        null,    null,          false],
            [20, 'Marcos',   'Cabrera Mendoza',    '612100020', '20202020W', null,                        null,    null,          false],
        ];
        $clientes = [];
        foreach ($clientesData as [$id, $nombre, $apellido, $tel, $dni, $dir, $cp, $ciudad, $factura]) {
            $clientes[] = ['id' => $id, 'nombre' => $nombre, 'apellido' => $apellido, 'telefono' => $tel, 'dni' => $dni, 'foto_dni' => null, 'direccion' => $dir, 'codigo_postal' => $cp, 'ciudad' => $ciudad, 'necesita_factura' => $factura, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }
        DB::table('clientes')->insert($clientes);

        // ============================================================
        // TRASTEROS (12 ocupados — clientes 1–12, 3 libres)
        // ============================================================
        $tamanyos  = ['Pequeño (5m²)', 'Mediano (10m²)', 'Grande (20m²)', 'Extra (30m²)'];
        $pisosTras = ['Planta Baja', 'Sótano'];
        $precios   = [60.00, 90.00, 150.00, 200.00];
        $trasteros = [];
        for ($i = 1; $i <= 15; $i++) {
            $idx         = ($i - 1) % 4;
            $pisoIdx     = ($i - 1) % 2;
            $clienteId   = $i <= 12 ? $i : null;
            $fechaInicio = $clienteId ? Carbon::now()->subMonths(rand(6, 24))->format('Y-m-d') : null;
            $trasteros[] = [
                'id'                    => $i,
                'numero'                => 'T-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'piso'                  => $pisosTras[$pisoIdx],
                'tamanyo'               => $tamanyos[$idx],
                'precio_mensual'        => $precios[$idx],
                'cliente_id'            => $clienteId,
                'fecha_inicio_alquiler' => $fechaInicio,
                'notas'                 => $clienteId ? null : 'Disponible',
                'created_at'            => Carbon::now(),
                'updated_at'            => Carbon::now(),
            ];
        }
        DB::table('trasteros')->insert($trasteros);

        // ============================================================
        // PISOS (5, los 3 primeros ocupados — clientes 13–15, 2 libres)
        // ============================================================
        DB::table('pisos')->insert([
            ['id' => 1, 'numero' => 'P-1A', 'piso' => '1º', 'precio_mensual' => 800.00, 'cliente_id' => 13, 'fecha_inicio_alquiler' => '2024-01-01', 'notas' => 'Piso exterior, 3 hab.',   'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'numero' => 'P-1B', 'piso' => '1º', 'precio_mensual' => 750.00, 'cliente_id' => 14, 'fecha_inicio_alquiler' => '2023-09-01', 'notas' => 'Piso interior, 2 hab.',  'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'numero' => 'P-2A', 'piso' => '2º', 'precio_mensual' => 850.00, 'cliente_id' => 15, 'fecha_inicio_alquiler' => '2024-06-01', 'notas' => 'Recién reformado, 3 hab.','created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'numero' => 'P-2B', 'piso' => '2º', 'precio_mensual' => 820.00, 'cliente_id' => null, 'fecha_inicio_alquiler' => null, 'notas' => 'Disponible', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 5, 'numero' => 'P-3A', 'piso' => '3º', 'precio_mensual' => 900.00, 'cliente_id' => null, 'fecha_inicio_alquiler' => null, 'notas' => 'Disponible', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        // ============================================================
        // PAGOS DE ALQUILER
        // 12 trasteros × 3 meses = 36 registros
        //  3 pisos    × 3 meses =  9 registros
        // Total: 45 pagos  → pages: 15+15+15
        // ============================================================
        $meses = [];
        for ($i = 2; $i >= 0; $i--) {
            $f = Carbon::now()->subMonths($i);
            $meses[] = ['mes' => (int)$f->month, 'anyo' => (int)$f->year];
        }

        $pagos    = [];
        $pagoId   = 1;
        $detalles = [];
        $detId    = 1;

        // Trasteros ocupados (clientes 1-12, trasteros 1-12)
        for ($t = 1; $t <= 12; $t++) {
            $clienteId = $t;
            $precio    = $precios[($t - 1) % 4];
            foreach ($meses as $idx => $m) {
                $pagado = match(true) {
                    $idx === 0                => $precio,          // mes más antiguo: pagado
                    $idx === 1 && $t % 3 === 0 => round($precio / 2, 2), // cada 3 trasteros: parcial
                    $idx === 1               => $precio,          // el resto del mes del medio: pagado
                    default                  => 0.00,             // mes actual: pendiente
                };
                $estado = match(true) {
                    $pagado >= $precio => 'pagado',
                    $pagado > 0        => 'parcial',
                    default            => 'pendiente',
                };
                $pagos[] = [
                    'id'            => $pagoId,
                    'cliente_id'    => $clienteId,
                    'tipo'          => 'trastero',
                    'referencia_id' => $t,
                    'numero'        => 'T-' . str_pad($t, 2, '0', STR_PAD_LEFT),
                    'mes'           => $m['mes'],
                    'anyo'          => $m['anyo'],
                    'importe_total' => $precio,
                    'pagado'        => $pagado,
                    'estado'        => $estado,
                    'notas'         => null,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ];
                if ($pagado > 0) {
                    $detalles[] = ['id' => $detId++, 'pago_alquiler_id' => $pagoId, 'importe' => $pagado, 'fecha_pago' => Carbon::create($m['anyo'], $m['mes'], 5)->format('Y-m-d'), 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
                }
                $pagoId++;
            }
        }

        // Pisos ocupados (clientes 13-15, pisos 1-3)
        $preciosPisos = [800.00, 750.00, 850.00];
        $pisoNumeros  = ['P-1A', 'P-1B', 'P-2A'];
        for ($p = 1; $p <= 3; $p++) {
            $clienteId = 12 + $p;
            $precio    = $preciosPisos[$p - 1];
            foreach ($meses as $idx => $m) {
                $pagado = ($idx < 2) ? $precio : 0.00;
                $estado = ($idx < 2) ? 'pagado' : 'pendiente';
                $pagos[] = [
                    'id'            => $pagoId,
                    'cliente_id'    => $clienteId,
                    'tipo'          => 'piso',
                    'referencia_id' => $p,
                    'numero'        => $pisoNumeros[$p - 1],
                    'mes'           => $m['mes'],
                    'anyo'          => $m['anyo'],
                    'importe_total' => $precio,
                    'pagado'        => $pagado,
                    'estado'        => $estado,
                    'notas'         => null,
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ];
                if ($pagado > 0) {
                    $detalles[] = ['id' => $detId++, 'pago_alquiler_id' => $pagoId, 'importe' => $pagado, 'fecha_pago' => Carbon::create($m['anyo'], $m['mes'], 5)->format('Y-m-d'), 'notas' => null, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
                }
                $pagoId++;
            }
        }

        DB::table('pagos_alquiler')->insert($pagos);
        DB::table('detalle_pagos_alquiler')->insert($detalles);

        // ============================================================
        // 20 GASTOS
        // ============================================================
        $tipos        = ['agua', 'luz', 'comunidad', 'mantenimiento', 'otro'];
        $tipoLabels   = ['Factura agua', 'Factura luz', 'Cuota comunidad', 'Mantenimiento', 'Otros gastos'];
        $mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $gastosRows  = [];
        $gDetalles   = [];
        $gastoDet    = 1;

        for ($g = 1; $g <= 20; $g++) {
            $tipoIdx        = ($g - 1) % 5;
            $tipo           = $tipos[$tipoIdx];
            $mesOffset      = $g - 1;            // cada gasto en un mes diferente (últimos 20 meses)
            $fechaEmision   = Carbon::now()->subMonths(19 - ($g - 1))->startOfMonth()->addDays(14);
            $fechaVenc      = (clone $fechaEmision)->addDays(30);
            $importe        = round(50 + ($g * 17.5), 2);
            $refTipo        = ($g % 4 === 0) ? 'piso' : (($g % 4 === 1) ? 'trastero' : 'general');
            $refId          = ($refTipo === 'piso') ? (($g % 3) + 1) : (($refTipo === 'trastero') ? (($g % 12) + 1) : null);

            // Estado: primeros 8 pagados, 9-14 parciales o pendientes variados, últimos 6 pendientes
            if ($g <= 8) {
                $pagado = $importe;
                $estado = 'pagado';
            } elseif ($g <= 14) {
                $pagado = ($g % 2 === 0) ? round($importe * 0.5, 2) : 0.00;
                $estado = ($g % 2 === 0) ? 'parcial' : 'pendiente';
            } else {
                $pagado = 0.00;
                $estado = 'pendiente';
            }

            $mes     = $mesesNombres[$fechaEmision->month - 1];
            $anyo    = $fechaEmision->year;
            $label   = $tipoLabels[$tipoIdx];

            $gastosRows[] = [
                'id'                => $g,
                'tipo'              => $tipo,
                'descripcion'       => "{$label} {$mes} {$anyo}",
                'referencia_tipo'   => $refTipo,
                'referencia_id'     => $refId,
                'fecha_emision'     => $fechaEmision->format('Y-m-d'),
                'fecha_vencimiento' => $fechaVenc->format('Y-m-d'),
                'importe_total'     => $importe,
                'pagado'            => $pagado,
                'estado'            => $estado,
                'notas'             => $g <= 8 ? 'Factura nº 20' . str_pad($g, 2, '0', STR_PAD_LEFT) : null,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];

            if ($pagado > 0) {
                $gDetalles[] = [
                    'id'         => $gastoDet++,
                    'gasto_id'   => $g,
                    'importe'    => $pagado,
                    'fecha_pago' => $fechaEmision->addDays(10)->format('Y-m-d'),
                    'notas'      => $estado === 'parcial' ? 'Pago parcial' : null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        DB::table('gastos')->insert($gastosRows);
        DB::table('detalle_gastos')->insert($gDetalles);

        $this->command->info('✅ Seeder completado: 20 clientes, 45 pagos, 20 gastos.');
    }
}

