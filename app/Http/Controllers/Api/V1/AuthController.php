<?php

namespace App\Http\Controllers\Api\V1; // <-- ESTO DEBE SER ASÍ (CON \V1)

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * Inicio de sesión móvil con emisión de token Sanctum y detección de rol.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.'
            ], 401);
        }

        $token = $user->createToken($request->device_name ?? 'mobile_device')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                // Extraemos solo el nombre como texto plano si es un objeto o relación:
                'role'           => is_object($user->role)
                    ? ($user->role->name ?? $user->role->nombre ?? 'User')
                    : ($user->role ?? 'User'),
                'terms_accepted' => !is_null($user->terms_accepted_at),
            ],
        ], 200);
    }

    /**
     * Cierra la sesión revocando el token del dispositivo.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.'
        ], 200);
    }

    public function acceptTerms(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.'
            ], 401);
        }

        $user->update([
            'terms_accepted_at' => now(),
            'terms_ip'          => $request->ip(),
            'terms_version'     => '1.0',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Términos y condiciones aceptados correctamente.'
        ], 200);
    }

    /**
     * Paso 1 Móvil: Envía el token al correo y valida que la cuenta exista.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email'    => 'Formato de correo no válido.'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No existe ninguna cuenta asociada a este correo electrónico.'
            ], 404);
        }

        // Genera el token de Laravel y dispara la notificación por correo
        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return response()->json([
            'success' => true,
            'message' => 'Código de recuperación enviado a tu correo.'
        ], 200);
    }

    /**
     * Paso 2 Móvil: Recibe el token, correo y nueva contraseña.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => ['required', 'string', 'confirmed', PasswordRule::min(6)],
            'password_confirmation' => 'required|string',
        ], [
            'token.required'     => 'Debes ingresar el código recibido.',
            'password.min'       => 'La contraseña debe tener mínimo 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Revoca tokens Sanctum anteriores por seguridad
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'El código o token de recuperación es inválido o ha expirado.'
        ], 400);
    }
}
