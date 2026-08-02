<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\ValidRfc;
use App\Rules\ValidCurp;
use App\Rules\ValidClabe;
use App\Rules\ValidNss;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Obtenemos la empresa actual del usuario
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Registra tu empresa primero.');
        }

        // 1. Recibir parámetros de búsqueda
        $search = $request->input('search');
        $status = $request->input('status', 'activos'); // Por defecto mostramos solo activos

        // 2. Construir la consulta base
        $query = Employee::where('company_id', $company->id);

        // 3. Filtro de Estatus Operativo
        if ($status === 'activos') {
            $query->where('is_active', true);
        } elseif ($status === 'inactivos') {
            $query->where('is_active', false);
        }
        // Si es 'todos', no aplicamos filtro de is_active

        // 4. Búsqueda Profunda (Live Search)
        // Agregamos 'email' y 'position' para hacer el buscador más potente
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('rfc', 'LIKE', "%{$search}%")
                    ->orWhere('curp', 'LIKE', "%{$search}%")
                    ->orWhere('nss', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        // 5. Paginación y persistencia de filtros en la URL
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

        // Validación estricta de datos (incluyendo los nuevos)
        $request->validate([
            'rfc' => ['required', 'string', new ValidRfc],
            'curp' => ['required', 'string', new ValidCurp],
            'full_name' => 'required|string|max:255',
            'nss' => ['required', 'string', new ValidNss],
            'base_salary' => 'required|numeric|min:0',
            // Nuevos campos
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:255',
            'cp' => ['nullable', 'string', 'regex:/^(?!00000)[0-9]{5}$/'],
            'clabe' => ['nullable', 'string', new ValidClabe],
            'hire_date' => 'nullable|date',
        ], [
            'cp.regex' => 'El Código Postal debe tener 5 dígitos y no puede ser 00000.',
        ]);

        // Inserción asegurando el company_id
        Employee::create([
            'company_id' => $company->id,
            'rfc' => strtoupper($request->rfc),
            'curp' => strtoupper($request->curp),
            'full_name' => $request->full_name,
            'nss' => $request->nss,
            'base_salary' => $request->base_salary,
            'email' => $request->email,
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

        // Blindaje: Solo puede editar si el empleado pertenece a su empresa
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
            // Nuevos campos
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:255',
            'cp' => ['nullable', 'string', 'regex:/^(?!00000)[0-9]{5}$/'],
            'clabe' => ['nullable', 'string', new ValidClabe],
            'hire_date' => 'nullable|date',
        ], [
            'cp.regex' => 'El Código Postal debe tener 5 dígitos y no puede ser 00000.',
        ]);

        $employee->update([
            'rfc' => strtoupper($request->rfc),
            'curp' => strtoupper($request->curp),
            'full_name' => $request->full_name,
            'nss' => $request->nss,
            'base_salary' => $request->base_salary,
            'email' => $request->email,
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

        // BAJA LÓGICA: Apagamos al empleado para que ya no genere nómina.
        $employee->update(['is_active' => false]);

        return redirect()->route('employees.index')->with('success', 'Empleado dado de baja exitosamente. Su historial financiero se mantiene intacto por auditoría.');
    }
}
