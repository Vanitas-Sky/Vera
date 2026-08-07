<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Rules\ValidRfc;

class OnboardingController extends Controller
{
    public function create()
    {
        // Traemos el catálogo del SAT para llenar el <select>
        $regimes = DB::table('sat_tax_regimes')->get();
        return view('onboarding', compact('regimes'));
    }

    public function store(Request $request)
    {
        // 1. Validación estricta
        $request->validate([
            'rfc' => ['required', 'string', new ValidRfc],
            'legal_name' => 'required|string|max:255',
            'postal_code' => ['required', 'string', 'regex:/^(?!00000)[0-9]{5}$/'], // Restringido exactamente a 5 caracteres
            'tax_regime_code' => 'required|exists:sat_tax_regimes,code',
            // En las reglas de validación de $request->validate([...])
            'industry' => 'nullable|string|max:150',
        ]);

        // 2. Crear la Empresa
        $company = Company::create([
            'rfc' => strtoupper($request->rfc),
            'legal_name' => $request->legal_name,
            'postal_code' => $request->postal_code,
            'tax_regime_code' => $request->tax_regime_code,
            'industry' => $request->industry,
        ]);

        // 3. Vincular al usuario actual como ADMIN_PYME usando la tabla pivote
        DB::table('user_companies')->insert([
            'user_id' => Auth::id(),
            'company_id' => $company->id,
            'role_in_company' => 'ADMIN_PYME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Liberar al usuario hacia el dashboard
        return redirect()->route('dashboard');
    }
}
