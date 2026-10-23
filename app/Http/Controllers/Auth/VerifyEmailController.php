<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Valida el enlace firmado, inicia sesión y manda al onboarding.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::findOrFail($request->route('id'));

        // 1. Validar que el hash corresponda al correo del usuario
        $expectedHash = sha1(strtolower($user->getEmailForVerification()));
        if (
            !hash_equals($expectedHash, (string) $request->route('hash')) &&
            !hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))
        ) {
            abort(403, 'El enlace de verificación no es válido o ha sido modificado.');
        }

        // 2. Si no estaba verificado, marcarlo y disparar el evento
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // 3. Iniciar sesión automáticamente en este navegador móvil
        Auth::login($user);
        $request->session()->regenerate();

        // 4. Redirigir según el estado de la empresa
        if (!$user->companies()->exists()) {
            return redirect()->route('onboarding')->with('status', '¡Correo verificado con éxito! Ahora registra tu empresa.');
        }

        return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
    }
}
