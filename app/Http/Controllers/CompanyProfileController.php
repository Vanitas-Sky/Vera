<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Rules\ValidRfc;

class CompanyProfileController extends Controller
{
    public function edit()
    {
        $company = Auth::user()->companies()->first();
        
        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'No tienes una empresa registrada.');
        }

        // Simulamos el catálogo oficial del SAT de Regímenes Fiscales
        $regimenesSAT = [
            '601' => 'General de Ley Personas Morales',
            '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
            '606' => 'Arrendamiento',
            '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
            '626' => 'Régimen Simplificado de Confianza (RESICO)',
        ];

        return view('company.profile', compact('company', 'regimenesSAT'));
    }

    public function update(Request $request)
    {
        $company = Auth::user()->companies()->first();

        $validated = $request->validate([
            'legal_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'rfc' => ['required', 'string', new ValidRfc],
            'postal_code' => ['required', 'string', 'regex:/^(?!00000)[0-9]{5}$/'],
            'tax_regime_code' => 'required|string|max:3',
            'industry' => 'nullable|string|max:150',
        ]);

        $company->update($validated);

        return redirect()->route('company.profile')->with('success', 'Perfil fiscal actualizado correctamente. Vera usará estas reglas para tus auditorías.');
    }
}
