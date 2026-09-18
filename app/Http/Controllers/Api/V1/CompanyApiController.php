<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyApiController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'No hay empresa vinculada.'], 404);
        }

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'company' => [
                'legal_name' => $company->legal_name,
                'trade_name' => $company->trade_name ?? $company->legal_name,
                'rfc' => $company->rfc,
                'tax_regime' => $company->tax_regime ?? '601 - General de Ley Personas Morales',
                'postal_code' => $company->postal_code ?? 'Sin C.P.',
            ],
            'server_status' => 'Conectado',
            'api_version' => '1.0.0 (Sanctum)'
        ]);
    }
}