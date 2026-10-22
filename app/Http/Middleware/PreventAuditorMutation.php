<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventAuditorMutation
{
    /**
     * Bloquea mutaciones de datos a usuarios con rol AUDITOR_EXTERNO.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAuditor()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Acción denegada: Tu cuenta tiene rol de Auditor Externo con permisos exclusivos de solo lectura.'
                ], 403);
            }

            return redirect()->back()->with('error', 'Acceso denegado: Los auditores externos solo cuentan con permisos de consulta y auditoría.');
        }

        return $next($request);
    }
}