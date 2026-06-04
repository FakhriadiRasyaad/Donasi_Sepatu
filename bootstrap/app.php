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

        /*
         * Jangan aktifkan statefulApi() untuk project ini.
         *
         * Alasannya:
         * - Frontend kamu pakai request API JSON.
         * - Backend login/register sudah berhasil via Postman.
         * - Auth memakai Sanctum token/Bearer token.
         * - Kalau statefulApi() aktif, request dari browser bisa dianggap
         *   sebagai cookie/session-based request, lalu Laravel minta CSRF token.
         */
        // $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Kembalikan semua validation exception sebagai JSON untuk API
        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors(),
            ], 422);
        });

        // Kembalikan semua authentication exception sebagai JSON untuk API
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token autentikasi tidak valid atau sudah expired. Silakan login kembali.',
            ], 401);
        });

        // Kembalikan not found sebagai JSON
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource tidak ditemukan.',
            ], 404);
        });

    })->create();