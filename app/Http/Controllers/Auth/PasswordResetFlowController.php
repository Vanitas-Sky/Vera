<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;

class PasswordResetFlowController extends Controller
{
    /**
     * Muestra la pantalla (detecta si ya se envió el código para mostrar Paso 1 o Paso 2).
     */
    public function show()
    {
        return view('auth.recuperar-contrasena', [
            'email' => session('recovery_email'),
            'step'  => session('recovery_email') ? 2 : 1
        ]);
    }

    /**
     * Paso 1: Genera y envía el token, conservando el correo en sesión.
     */
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ], [
            'email.required' => 'Ingresa tu correo electrónico registrado.',
            'email.email'    => 'Ingresa un formato de correo válido.'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput()->withErrors([
                'email' => 'No encontramos ninguna cuenta asociada a este correo.'
            ]);
        }

        // Genera el token y envía el correo con la notificación
        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        // Guardamos el correo en sesión para el Paso 2
        return redirect()->route('password.recovery.show')
            ->with('recovery_email', $request->email)
            ->with('status', 'Hemos enviado tu código de validación. Búscalo en tu bandeja de entrada o spam.');
    }

    /**
     * Paso 2: Recibe el token y la nueva contraseña sin pedir el correo otra vez.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => ['required', 'confirmed', PasswordRule::min(6)],
            'password_confirmation' => 'required',
        ], [
            'token.required'     => 'Ingresa el código o token que recibiste.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres.'
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->tokens()->delete(); // Cierra sesiones previas
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->forget('recovery_email');
            return redirect('/login')->with('success', 'Tu contraseña ha sido restablecida con éxito. Inicia sesión con tu nueva clave.');
        }

        // Si el token es inválido o expiró
        return back()
            ->with('recovery_email', $request->email)
            ->withErrors(['token' => 'El código de recuperación es incorrecto o ya expiró.']);
    }

    /**
     * Permite al usuario regresar al Paso 1 si colocó mal su correo.
     */
    public function restart()
    {
        session()->forget('recovery_email');
        return redirect()->route('password.recovery.show');
    }
}