<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\ValidRfc;
use App\Rules\ValidCurp;
use App\Rules\ValidClabe;
use App\Rules\ValidNss;
use App\Rules\ValidPhone; // Si creaste la regla, o usa la regex directa abajo

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Registra tu empresa primero.');
        }

        $search = $request->input('search');
        $status = $request->input('status', 'activos');

        $query = Employee::where('company_id', $company->id);

        if ($status === 'activos') {
            $query->where('is_active', true);
        } elseif ($status === 'inactivos') {
            $query->where('is_active', false);
        }

        // Búsqueda Profunda (incluyendo teléfono)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('rfc', 'LIKE', "%{$search}%")
                    ->orWhere('curp', 'LIKE', "%{$search}%")
                    ->orWhere('nss', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        $employees = $query->orderBy('full_name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search', 'status'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $company = Auth::user()->companies()->first();

        $request->validate([
            'rfc' => ['required', 'string', new ValidRfc],
            'curp' => ['required', 'string', new ValidCurp],
            'full_name' => 'required|string|max:255',
            'nss' => ['required', 'string', new ValidNss],
            'base_salary' => 'required|numeric|min:0',
            'periodicity' => 'required|in:mensual,quincenal,semanal',
            'work_regime' => 'required|string|max:100',
            // Campos Opcionales
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'regex:/^[1-9][0-9]{9}$/'], // Exactamente 10 dígitos numéricos
            'position' => 'nullable|string|max:255',
            'cp' => ['nullable', 'string', 'regex:/^(?!00000)[0-9]{5}$/'],
            'clabe' => ['nullable', 'string', new ValidClabe],
            'hire_date' => 'nullable|date',
        ], [
            'cp.regex' => 'El Código Postal debe tener 5 dígitos y no puede ser 00000.',
            'phone.regex' => 'El teléfono debe constar de 10 dígitos válidos (sin clave de país ni ceros al inicio).',
            'periodicity.in' => 'Selecciona un periodo de pago válido.',
        ]);

        // Limpieza de formato antes de guardar
        $cleanPhone = $request->phone ? preg_replace('/[^0-9]/', '', $request->phone) : null;

        Employee::create([
            'company_id' => $company->id,
            'rfc' => strtoupper($request->rfc),
            'curp' => strtoupper($request->curp),
            'full_name' => $request->full_name,
            'nss' => $request->nss,
            'base_salary' => $request->base_salary,
            'periodicity' => $request->periodicity,
            'work_regime' => $request->work_regime,
            'email' => $request->email,
            'phone' => $cleanPhone,
            'position' => $request->position,
            'cp' => $request->cp,
            'clabe' => $request->clabe,
            'hire_date' => $request->hire_date,
            'is_active' => true,
        ]);

        return redirect()->route('employees.index')->with('success', 'Empleado registrado exitosamente.');
    }

    public function show(Employee $employee)
    {
        //
    }

    public function edit($id)
    {
        $company = Auth::user()->companies()->first();
        $employee = Employee::where('company_id', $company->id)->findOrFail($id);

        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, $id)
    {
        $company = Auth::user()->companies()->first();
        $employee = Employee::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'rfc' => ['required', 'string', new ValidRfc],
            'curp' => ['required', 'string', new ValidCurp],
            'full_name' => 'required|string|max:255',
            'nss' => ['required', 'string', new ValidNss],
            'base_salary' => 'required|numeric|min:0',
            'periodicity' => 'required|in:mensual,quincenal,semanal',
            'work_regime' => 'required|string|max:100',
            // Campos Opcionales
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'regex:/^[1-9][0-9]{9}$/'], // Exactamente 10 dígitos numéricos
            'position' => 'nullable|string|max:255',
            'cp' => ['nullable', 'string', 'regex:/^(?!00000)[0-9]{5}$/'],
            'clabe' => ['nullable', 'string', new ValidClabe],
            'hire_date' => 'nullable|date',
        ], [
            'cp.regex' => 'El Código Postal debe tener 5 dígitos y no puede ser 00000.',
            'phone.regex' => 'El teléfono debe constar de 10 dígitos válidos (sin clave de país ni ceros al inicio).',
            'periodicity.in' => 'Selecciona un periodo de pago válido.',
        ]);

        $cleanPhone = $request->phone ? preg_replace('/[^0-9]/', '', $request->phone) : null;

        $employee->update([
            'rfc' => strtoupper($request->rfc),
            'curp' => strtoupper($request->curp),
            'full_name' => $request->full_name,
            'nss' => $request->nss,
            'base_salary' => $request->base_salary,
            'periodicity' => $request->periodicity,
            'work_regime' => $request->work_regime,
            'email' => $request->email,
            'phone' => $cleanPhone,
            'position' => $request->position,
            'cp' => $request->cp,
            'clabe' => $request->clabe,
            'hire_date' => $request->hire_date,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('employees.index')->with('success', 'Datos del empleado actualizados correctamente.');
    }

    public function destroy($id)
    {
        $company = Auth::user()->companies()->first();
        $employee = Employee::where('company_id', $company->id)->findOrFail($id);

        $employee->update(['is_active' => false]);

        return redirect()->route('employees.index')->with('success', 'Empleado dado de baja exitosamente. Su historial financiero se mantiene intacto por auditoría.');
    }
}
