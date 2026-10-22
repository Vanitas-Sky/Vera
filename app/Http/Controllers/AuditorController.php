<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuditorController extends Controller
{
    /**
     * Lista de auditores asignados a la empresa actual.
     */
    public function index()
    {
        $company = Auth::user()->companies()->firstOrFail();

        // Auditores vinculados a esta empresa
        $auditors = $company->users()
            ->whereHas('role', function ($q) {
                $q->where('name', Role::AUDITOR_EXTERNO);
            })
            ->get();

        return view('auditors.index', compact('company', 'auditors'));
    }

    /**
     * Da de alta un nuevo auditor y lo asocia a la empresa.
     */
    public function store(Request $request)
    {
        $company = Auth::user()->companies()->firstOrFail();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ], [
            'email.unique' => 'Ya existe un usuario registrado con este correo institucional.',
        ]);

        $auditorRole = Role::where('name', Role::AUDITOR_EXTERNO)->firstOrFail();

        // Crear el usuario auditor con verificación automática de correo
        $auditor = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role_id'           => $auditorRole->id,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Vincular a la empresa actual en la tabla pivote user_companies
        $company->users()->attach($auditor->id, [
            'role_in_company' => 'AUDITOR',
        ]);

        return redirect()->route('auditors.index')
            ->with('success', "Auditor {$auditor->name} registrado exitosamente. Ya puede iniciar sesión con sus credenciales.");
    }

    /**
     * Revoca el acceso de un auditor a la empresa.
     */
    public function destroy($id)
    {
        $company = Auth::user()->companies()->firstOrFail();

        $auditor = $company->users()->where('users.id', $id)->firstOrFail();

        // Desvincular de la empresa
        $company->users()->detach($auditor->id);

        return redirect()->route('auditors.index')
            ->with('success', "Acceso de auditoría revocado para {$auditor->name}.");
    }
}