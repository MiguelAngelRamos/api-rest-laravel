<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
<<<<<<< HEAD
        // Verificar si el usuario autenticado tiene el rol requerido
        if ($request->user()->role !== $role) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

=======
        // verificar si el usuario tiene el rol requerido admin
        if ($request->user()->role !== $role) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
>>>>>>> f0af8a7b11fbfb0237be5041d7365386c2f767aa
        return $next($request);
    }
}
