<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está autenticado pero no tiene ninguna empresa vinculada
        if (Auth::check() && Auth::user()->companies()->count() === 0) {
            // Y si no está ya en la ruta de onboarding (para evitar un bucle infinito)
            if (! $request->routeIs('onboarding*')) {
                return redirect()->route('onboarding');
            }
        }

        return $next($request);
    }
}
