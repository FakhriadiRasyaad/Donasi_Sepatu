<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Daftarkan alias middleware custom
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Izinkan semua request ke /api (CORS dihandle via config/cors.php)
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Kembalikan semua exception sebagai JSON untuk API
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token autentikasi tidak valid atau sudah expired. Silakan login kembali.',
            ], 401);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource tidak ditemukan.',
            ], 404);
        });

    })->create();
