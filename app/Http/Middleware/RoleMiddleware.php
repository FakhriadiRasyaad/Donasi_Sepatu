<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Pastikan user yang login memiliki role yang dibutuhkan.
     * Dipakai di route admin: ->middleware('role:admin')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user()->role !== $role) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak. Halaman ini hanya untuk ' . $role . '.',
            ], 403);
        }

        return $next($request);
    }
}
