<?php

use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\FacturaController;
use App\Http\Controllers\Api\GastoController;
use App\Http\Controllers\Api\PagoAlquilerController;
use App\Http\Controllers\Api\PisoController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\TamanyoTrasteroController;
use App\Http\Controllers\Api\MantenimientoController;
use App\Http\Controllers\Api\TrasteroController;
use Illuminate\Support\Facades\Route;

// Clientes
Route::get('logo', function () {
    $path = public_path('logo.jpg');
    if (!file_exists($path)) abort(404);
    return response()->file($path, ['Content-Type' => 'image/jpeg']);
});

Route::get('clientes/list-all', [ClienteController::class, 'listAll']);
Route::get('clientes/{id}/pendiente-total', [ClienteController::class, 'pendienteTotal']);
Route::apiResource('clientes', ClienteController::class);

// Trasteros
Route::apiResource('trasteros', TrasteroController::class);

// Pisos
Route::apiResource('pisos', PisoController::class);

// Pagos de Alquiler
Route::get('pagos-alquiler', [PagoAlquilerController::class, 'index']);
Route::post('pagos-alquiler', [PagoAlquilerController::class, 'store']);
Route::get('pagos-alquiler/{pagoAlquiler}', [PagoAlquilerController::class, 'show']);
Route::delete('pagos-alquiler/{pagoAlquiler}', [PagoAlquilerController::class, 'destroy']);
Route::post('pagos-alquiler/registrar-pago', [PagoAlquilerController::class, 'registrarPago']);
Route::delete('pagos-alquiler/{pagoAlquiler}/detalles/{detalle}', [PagoAlquilerController::class, 'eliminarDetalle']);
Route::post('pagos-alquiler/enviar-recibo-email', [PagoAlquilerController::class, 'enviarReciboEmail']);

// Gastos
Route::apiResource('gastos', GastoController::class);
Route::post('gastos/{gasto}/pago', [GastoController::class, 'registrarPago']);
Route::delete('gastos/{gasto}/detalles/{detalle}', [GastoController::class, 'eliminarDetalle']);
Route::post('gastos/{gasto}/imagenes', [GastoController::class, 'subirImagenes']);
Route::delete('gastos/{gasto}/imagenes/{imagen}', [GastoController::class, 'eliminarImagen']);

// Relatorios
Route::prefix('relatorios')->group(function () {
    Route::get('estado-trasteros', [RelatorioController::class, 'estadoTrasteros']);
    Route::get('estado-pisos', [RelatorioController::class, 'estadoPisos']);
    Route::get('estado-pagos', [RelatorioController::class, 'estadoPagos']);
    Route::get('estado-gastos', [RelatorioController::class, 'estadoGastos']);
    Route::get('resumen-general', [RelatorioController::class, 'resumenGeneral']);
});

// Mantenimiento
Route::get('tamanyo-trasteros', [TamanyoTrasteroController::class, 'index']);
Route::post('tamanyo-trasteros', [TamanyoTrasteroController::class, 'store']);
Route::put('tamanyo-trasteros/{tamanyoTrastero}', [TamanyoTrasteroController::class, 'update']);
Route::delete('tamanyo-trasteros/{tamanyoTrastero}', [TamanyoTrasteroController::class, 'destroy']);

// Facturas
Route::get('facturas', [FacturaController::class, 'index']);
Route::post('facturas/enviar-email', [FacturaController::class, 'enviarEmail']);

// Mantenimiento - acciones
Route::post('mantenimiento/generar-pagos', [MantenimientoController::class, 'generarPagos']);
Route::get('mantenimiento/backups',        [MantenimientoController::class, 'listarBackups']);
Route::post('mantenimiento/backup',        [MantenimientoController::class, 'backup']);
Route::post('mantenimiento/restore',       [MantenimientoController::class, 'restore']);
Route::delete('mantenimiento/backup',      [MantenimientoController::class, 'deleteBackup']);
