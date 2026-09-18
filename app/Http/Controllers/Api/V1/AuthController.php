<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Genera el token con Laravel Sanctum
        $token = $user->createToken('vera-mobile-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'message' => 'Autenticación exitosa'
        ]);
    }

    public function logout(Request $request)
    {
        // Elimina el token actual que se usó para autenticar la petición móvil
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
