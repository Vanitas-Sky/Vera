<?php

namespace App\Http\Controllers;

use App\Models\FixedExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FixedExpenseController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();

        // ==========================================
        // VÍA A: MATEMÁTICA Y ALERTAS GLOBALES
        // ==========================================
        // Solo traemos los activos para los cálculos reales, sin importar la página
        $activeExpenses = \App\Models\FixedExpense::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        // --- CÁLCULOS PARA LOS KPIS (NUEVO) ---
        $totalMonthlyOpex = $activeExpenses->sum('monthly_amount');
        $annualProjection = $totalMonthlyOpex * 12; // Proyección a 12 meses
        $activeContractsCount = $activeExpenses->count(); // Total de contratos activos
        // --------------------------------------

        $alerts = [];
        $today = \Carbon\Carbon::now();

        foreach ($activeExpenses as $expense) {
            // ... (Tu código de alertas de vencimiento y pago próximo se queda EXACTAMENTE igual) ...
            // 1. Alerta de Vencimiento de Contrato / Póliza
            if ($expense->contract_end_date) {
                $daysToRenew = $today->diffInDays($expense->contract_end_date, false);

                if ($daysToRenew > 0 && $daysToRenew <= 60) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => "El contrato de {$expense->provider_name} ({$expense->category}) vence en {$daysToRenew} días.",
                        'action' => 'Renovar póliza/contrato'
                    ];
                } elseif ($daysToRenew <= 0) {
                    $alerts[] = [
                        'type' => 'danger',
                        'message' => "El contrato de {$expense->provider_name} ha EXPIRADO.",
                        'action' => 'Actualizar datos'
                    ];
                }
            }

            // 2. Alerta de Pago Próximo
            $currentDay = $today->day;
            $daysToPay = $expense->due_day - $currentDay;

            if ($daysToPay >= 0 && $daysToPay <= 5) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => "Pago próximo: {$expense->provider_name} por $" . number_format($expense->monthly_amount, 2) . " vence el día {$expense->due_day}.",
                    'action' => 'Preparar flujo'
                ];
            }
        }

        // ==========================================
        // VÍA B: FILTROS Y PAGINACIÓN PARA LA TABLA
        // ==========================================
        $search = $request->input('search');
        $category = $request->input('category', 'todas');
        $status = $request->input('status', 'activos');

        $query = \App\Models\FixedExpense::where('company_id', $company->id);

        // ... (Tu código de filtros se queda EXACTAMENTE igual) ...
        // Filtro Estatus
        if ($status === 'activos') {
            $query->where('is_active', true);
        } elseif ($status === 'inactivos') {
            $query->where('is_active', false);
        }

        // Filtro Categoría
        if ($category !== 'todas') {
            $query->where('category', $category);
        }

        // Buscador de Texto (Proveedor o Descripción)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('provider_name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Paginación conservando la URL
        $expenses = $query->orderBy('is_active', 'desc')
            ->orderBy('due_day')
            ->paginate(10)
            ->withQueryString();

        // --- ACTUALIZAR LA FUNCIÓN COMPACT() (NUEVO) ---
        return view('expenses.fixed.index', compact(
            'expenses',
            'totalMonthlyOpex',
            'annualProjection',     // <- Nueva variable
            'activeContractsCount', // <- Nueva variable
            'alerts',
            'search',
            'category',
            'status'
        ));
    }

    public function create()
    {
        return view('expenses.fixed.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider_name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'monthly_amount' => 'required|numeric|min:0',
            'due_day' => 'required|integer|min:1|max:31',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
        ], [
            'contract_end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'due_day.max' => 'El día de pago no puede ser mayor a 31.',
        ]);

        $company = Auth::user()->companies()->first();

        // Inyectamos valores por defecto antes de guardar
        $validated['company_id'] = $company->id;
        $validated['is_active'] = true;

        FixedExpense::create($validated);

        return redirect()->route('opex.index')->with('success', 'Contrato o póliza registrada exitosamente.');
    }

    // Mostrar el formulario de edición
    public function edit($id)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();

        // Bloqueo de seguridad: solo trae el contrato si pertenece a esta empresa
        $expense = \App\Models\FixedExpense::where('company_id', $company->id)->findOrFail($id);

        return view('expenses.fixed.edit', compact('expense'));
    }

    // Guardar los cambios del formulario
    public function update(\Illuminate\Http\Request $request, $id)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();
        $expense = \App\Models\FixedExpense::where('company_id', $company->id)->findOrFail($id);

        $request->validate([
            'provider_name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'due_day' => 'required|integer|min:1|max:31',
            'monthly_amount' => 'required|numeric|min:0',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
        ]);

        $expense->update($request->all());

        return redirect()->route('opex.index')->with('success', 'El contrato se ha actualizado correctamente.');
    }

    // Cambiar el estatus (Baja Lógica / Reactivación)
    public function toggleStatus($id)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();
        $expense = \App\Models\FixedExpense::where('company_id', $company->id)->findOrFail($id);

        // Invertimos el estatus actual
        $expense->is_active = !$expense->is_active;
        $expense->save();

        $status = $expense->is_active ? 'reactivado' : 'dado de baja';

        return redirect()->route('opex.index')->with('success', "El contrato ha sido {$status} correctamente.");
    }
}
