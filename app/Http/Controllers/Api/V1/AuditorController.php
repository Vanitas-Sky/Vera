<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuditorController extends Controller
{
    /**
     * Lista los auditores asignados a la empresa del admin autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada.'
            ], 404);
        }

        // Buscamos el ID correspondiente al rol de Auditor
        $auditorRole = Role::whereRaw('LOWER(name) LIKE ?', ['%auditor%'])->first();

        // Obtenemos los usuarios filtrando por la relación de rol o por role_id
        $auditors = $company->users()
            ->where(function ($query) use ($auditorRole) {
                if ($auditorRole) {
                    $query->where('users.role_id', $auditorRole->id);
                } else {
                    $query->whereHas('role', function ($q) {
                        $q->whereRaw('LOWER(name) LIKE ?', ['%auditor%']);
                    });
                }
            })
            ->get(['users.id', 'users.name', 'users.email', 'users.created_at']);

        return response()->json([
            'success' => true,
            'auditors' => $auditors
        ], 200);
    }

    /**
     * Registra y vincula un nuevo auditor a la empresa.
     */
    public function store(Request $request)
    {
        $admin = $request->user();
        $company = $admin->companies()->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una empresa activa asignada.'
            ], 404);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'string', Password::min(6)],
        ], [
            'email.unique' => 'Ya existe un usuario registrado con este correo.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.'
        ]);

        // 1. Obtener el registro del rol Auditor en la tabla roles
        $auditorRole = Role::whereRaw('LOWER(name) LIKE ?', ['%auditor%'])->first();

        // 2. Crear el usuario asignando role_id (sin usar la columna inexistente 'role')
        $auditor = User::create([
            'name'              => trim($request->name),
            'email'             => strtolower(trim($request->email)),
            'password'          => Hash::make($request->password),
            'role_id'           => $auditorRole?->id, // Asignación de llave foránea correcta
            'email_verified_at' => now(),
            'terms_accepted_at' => null,
            'terms_version'     => '1.0',
        ]);

        // Si tu proyecto usa asignación de roles por Spatie además de la columna:
        if (method_exists($auditor, 'assignRole') && $auditorRole) {
            $auditor->assignRole($auditorRole->name);
        }

        // 3. Vincular a la empresa en la tabla pivote company_user
        $company->users()->syncWithoutDetaching([$auditor->id]);

        return response()->json([
            'success' => true,
            'message' => 'Auditor registrado y asignado exitosamente.',
            'auditor' => [
                'id'    => $auditor->id,
                'name'  => $auditor->name,
                'email' => $auditor->email,
            ]
        ], 201);
    }

    /**
     * Da de baja al auditor revocando el acceso a la empresa.
     */
    public function destroy(Request $request, $id)
    {
        $admin = $request->user();
        $company = $admin->companies()->first();

        $auditor = User::findOrFail($id);

        // Desvincular de la empresa
        $company->users()->detach($auditor->id);

        // Revocar tokens activos de Sanctum para cerrar sesiones de inmediato
        $auditor->tokens()->delete();

        // Si ya no pertenece a ninguna empresa, se remueve el usuario
        if ($auditor->companies()->count() === 0) {
            $auditor->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Acceso revocado exitosamente.'
        ], 200);
    }
}