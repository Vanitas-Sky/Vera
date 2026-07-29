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
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        //
    }
}
