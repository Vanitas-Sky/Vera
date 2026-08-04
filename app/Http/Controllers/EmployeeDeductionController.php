<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDeductionController extends Controller
{
    /**
     * Muestra el panel de control de deducciones de un empleado específico.
     */
    public function index(Employee $employee)
    {
        $company = Auth::user()->companies()->first();

        // Seguridad: Asegurarnos que el empleado pertenece a la empresa actual
        if ($employee->company_id !== $company->id) {
            abort(403, 'Acceso denegado a este empleado.');
        }

        // Traemos las deducciones (activas e inactivas para el historial)
        $deductions = $employee->deductions()->orderBy('is_active', 'desc')->orderBy('created_at', 'desc')->get();

        return view('employees.deductions.index', compact('employee', 'deductions'));
    }

    /**
     * Guarda una nueva deducción.
     */
    public function store(Request $request, Employee $employee)
    {
        $company = Auth::user()->companies()->first();

        if ($employee->company_id !== $company->id) {
            abort(403);
        }

        // Validación estricta
        $request->validate([
            'sat_key' => 'required|string|size:3',
            'description' => 'required|string|max:255',
            'amount_type' => 'required|in:fixed,percentage,vsm',
            'amount' => 'required|numeric|min:0.01',
        ], [
            'sat_key.size' => 'La clave del SAT debe tener exactamente 3 caracteres (Ej: 004, 007).',
            'amount.min' => 'El monto o porcentaje debe ser mayor a cero.',
        ]);

        EmployeeDeduction::create([
            'employee_id' => $employee->id,
            'sat_key' => $request->sat_key,
            'description' => $request->description,
            'amount_type' => $request->amount_type,
            'amount' => $request->amount,
            'is_active' => true,
        ]);

        return redirect()->route('employees.deductions.index', $employee->id)
            ->with('success', 'Deducción asignada correctamente. Se aplicará en la próxima nómina calculada.');
    }

    /**
     * Desactiva una deducción (Soft Delete lógico para no romper historial de recibos)
     */
    public function destroy(Employee $employee, EmployeeDeduction $deduction)
    {
        $company = Auth::user()->companies()->first();

        if ($employee->company_id !== $company->id || $deduction->employee_id !== $employee->id) {
            abort(403);
        }

        // En lugar de borrar la fila de la base de datos (lo que rompería la integridad 
        // de nóminas pasadas si hiciéramos joins), simplemente la desactivamos.
        $deduction->update(['is_active' => false]);

        return redirect()->route('employees.deductions.index', $employee->id)
            ->with('success', 'La deducción ha sido detenida. Ya no se cobrará en futuras nóminas.');
    }
}
