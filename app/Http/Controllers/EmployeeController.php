<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Http\Request; // <-- Agrega esto para Request
use Illuminate\Support\Facades\Auth; // <-- Agrega esto para Auth

class EmployeeController extends Controller
{
    public function index()
    {
        // Obtenemos la empresa actual del usuario (tomamos la primera por ahora)
        $company = Auth::user()->companies()->first();

        // Traemos solo los empleados de ESTA empresa
        $employees = Employee::where('company_id', $company->id)->get();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $company = Auth::user()->companies()->first();

        // Validación estricta de datos
        $request->validate([
            'rfc' => 'required|string|min:12|max:13',
            'curp' => 'required|string|size:18',
            'full_name' => 'required|string|max:255',
            'nss' => 'required|string|size:11', // Número de Seguridad Social (11 dígitos)
            'base_salary' => 'required|numeric|min:0', // Salario mensual o quincenal base
        ]);

        // Inserción asegurando el company_id
        Employee::create([
            'company_id' => $company->id,
            'rfc' => strtoupper($request->rfc),
            'curp' => strtoupper($request->curp),
            'full_name' => $request->full_name,
            'nss' => $request->nss,
            'base_salary' => $request->base_salary,
            'is_active' => true,
        ]);

        return redirect()->route('employees.index')->with('success', 'Empleado registrado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $company = Auth::user()->companies()->first();
        
        // Blindaje: Solo puede editar si el empleado pertenece a su empresa
        $employee = Employee::where('company_id', $company->id)->findOrFail($id);

        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $company = Auth::user()->companies()->first();
        $employee = Employee::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'rfc' => 'required|string|min:12|max:13',
            'curp' => 'required|string|size:18',
            'full_name' => 'required|string|max:255',
            'nss' => 'required|string|size:11',
            'base_salary' => 'required|numeric|min:0',
            // is_active vendrá como 'on' si el checkbox está marcado
        ]);

        $employee->update([
            'rfc' => strtoupper($request->rfc),
            'curp' => strtoupper($request->curp),
            'full_name' => $request->full_name,
            'nss' => $request->nss,
            'base_salary' => $request->base_salary,
            // Si el checkbox de activo está marcado, es true, de lo contrario false
            'is_active' => $request->has('is_active'), 
        ]);

        return redirect()->route('employees.index')->with('success', 'Datos del empleado actualizados correctamente.');
    }

    /**
     * Remove the specified resource from storage (Baja lógica, no eliminación física).
     */
    public function destroy($id)
    {
        $company = Auth::user()->companies()->first();
        $employee = Employee::where('company_id', $company->id)->findOrFail($id);

        // BAJA LÓGICA: Apagamos al empleado para que ya no genere nómina.
        $employee->update(['is_active' => false]);

        return redirect()->route('employees.index')->with('success', 'Empleado dado de baja exitosamente. Su historial financiero se mantiene intacto por auditoría.');
    }
}
