<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS: permitir peticiones del frontend Vue
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $errors = $e->errors();

                return response()->json([
                    'message' => collect($errors)->flatten()->first() ?? 'Los datos enviados no son válidos.',
                    'errors' => $errors,
                ], 422);
            }

            // Laravel convierte ModelNotFoundException (route-model-binding o
            // findOrFail) en NotFoundHttpException antes de llegar aquí.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'message' => 'El recurso solicitado no existe.',
                ], 404);
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Error en la petición.',
                ], $e->getStatusCode());
            }

            // Cualquier otro error (BD, PHP, etc.): se registra en el log
            // pero no se expone el mensaje interno al cliente.
            report($e);

            return response()->json([
                'message' => 'Error interno del servidor. Inténtalo de nuevo.',
            ], 500);
        });
    })->create();
