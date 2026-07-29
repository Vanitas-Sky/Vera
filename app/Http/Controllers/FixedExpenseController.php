<?php

namespace App\Http\Controllers;

use App\Models\FixedExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FixedExpenseController extends Controller
{
    public function index()
    {
        $company = Auth::user()->companies()->first();
        
        $expenses = FixedExpense::where('company_id', $company->id)
                                ->where('is_active', true)
                                ->orderBy('due_day')
                                ->get();

        $totalMonthlyOpex = $expenses->sum('monthly_amount');

        // Módulo 1: Sistema de Alertas y Vencimientos
        $alerts = [];
        $today = Carbon::now();

        foreach ($expenses as $expense) {
            // 1. Alerta de Vencimiento de Contrato / Póliza (Próximos 60 días)
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

            // 2. Alerta de Pago Próximo (Próximos 5 días del mes actual)
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

        return view('expenses.fixed.index', compact('expenses', 'totalMonthlyOpex', 'alerts'));
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
}
