<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FixedExpense;
use Carbon\Carbon;

class FixedExpenseApiController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada.'], 404);
        }

        // 1. Contratos Activos y Métricas Globales
        $activeExpenses = FixedExpense::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $totalMonthlyOpex = (float) $activeExpenses->sum('monthly_amount');
        $annualProjection = (float) ($totalMonthlyOpex * 12);
        $activeContractsCount = $activeExpenses->count();

        // 2. Alertas idénticas a la Web
        $alerts = [];
        $today = Carbon::now();

        foreach ($activeExpenses as $expense) {
            // Alerta de Contrato / Póliza
            if ($expense->contract_end_date) {
                $daysToRenew = $today->diffInDays($expense->contract_end_date, false);

                if ($daysToRenew > 0 && $daysToRenew <= 60) {
                    $alerts[] = [
                        'type' => 'warning',
                        'title' => 'Vencimiento Próximo',
                        'message' => "El contrato de {$expense->provider_name} ({$expense->category}) vence en {$daysToRenew} días.",
                        'action' => 'Renovar póliza/contrato'
                    ];
                } elseif ($daysToRenew <= 0) {
                    $alerts[] = [
                        'type' => 'danger',
                        'title' => 'Contrato Expirado',
                        'message' => "El contrato de {$expense->provider_name} ha EXPIRADO.",
                        'action' => 'Actualizar datos'
                    ];
                }
            }

            // Alerta de Pago Próximo
            $currentDay = $today->day;
            $daysToPay = $expense->due_day - $currentDay;

            if ($daysToPay >= 0 && $daysToPay <= 5) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Pago Próximo',
                    'message' => "Pago próximo: {$expense->provider_name} por $" . number_format($expense->monthly_amount, 2) . " vence el día {$expense->due_day}.",
                    'action' => 'Preparar flujo'
                ];
            }
        }

        // 3. Listado Completo de Gastos Fijos
        $expenses = FixedExpense::where('company_id', $company->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('due_day', 'asc')
            ->get()
            ->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'provider_name' => $exp->provider_name,
                    'category' => $exp->category,
                    'description' => $exp->description,
                    'monthly_amount' => (float) $exp->monthly_amount,
                    'due_day' => (int) $exp->due_day,
                    'contract_start_date' => $exp->contract_start_date ? Carbon::parse($exp->contract_start_date)->format('d/m/Y') : null,
                    'contract_end_date' => $exp->contract_end_date ? Carbon::parse($exp->contract_end_date)->format('d/m/Y') : null,
                    'is_active' => (bool) $exp->is_active
                ];
            });

        return response()->json([
            'total_monthly_opex' => $totalMonthlyOpex,
            'annual_projection' => $annualProjection,
            'active_contracts_count' => $activeContractsCount,
            'alerts' => $alerts,
            'expenses' => $expenses
        ]);
    }
}