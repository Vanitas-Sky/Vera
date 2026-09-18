<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\BankTransaction;
use App\Models\FixedExpense;
use App\Models\PayrollPeriod;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    public function summary(Request $request)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Primero debes registrar tu empresa.'], 404);
        }

        $selectedPeriod = $request->input('period', now()->format('Y-m'));
        $currentYear = (int) substr($selectedPeriod, 0, 4);
        $currentMonth = (int) substr($selectedPeriod, 5, 2);

        // 1. Facturas de Ingreso (I)
        $incomes = Invoice::where('company_id', $company->id)
            ->where('type', 'I')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->get();

        $totalIncome = (float) $incomes->sum('total');
        $subtotalIncome = (float) $incomes->sum('subtotal');

        // 2. Facturas de Egreso (E)
        $expenses = Invoice::where('company_id', $company->id)
            ->where('type', 'E')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->get();

        $totalExpense = (float) $expenses->sum('total');
        $subtotalExpense = (float) $expenses->sum('subtotal');

        // 3. Banco y Discrepancia real (Solo si retiros > facturas)
        $bankWithdrawals = (float) BankTransaction::where('company_id', $company->id)
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('withdrawal');

        $rawDiscrepancy = $bankWithdrawals - $totalExpense;
        $discrepancy = $rawDiscrepancy > 0 ? $rawDiscrepancy : 0.0; // Si es negativo, no hay discrepancia fiscal

        // 4. Costo de Nóminas del mes (IGUAL A LA WEB)
        $payrolls = PayrollPeriod::where('company_id', $company->id)
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->get();

        $totalPayrollGross = (float) $payrolls->sum('total_gross');

        // 5. Proyección OpEx / Fuga de comprobantes
        $projectedOpex = (float) FixedExpense::where('company_id', $company->id)
            ->where('is_active', true)
            ->sum('monthly_amount');

        $missingInvoicesAmount = ($projectedOpex > $subtotalExpense) ? ($projectedOpex - $subtotalExpense) : 0;

        // 6. Utilidad Neta Preliminar y Semáforo (Cálculo exacto de la Web)
        $netProfit = $subtotalIncome - $subtotalExpense - $totalPayrollGross;
        $taxBurdenRatio = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        $semaforo = 'verde';
        $mensajeSemaforo = 'Tu balance financiero y deducciones se encuentran en un nivel saludable para este mes.';

        if ($taxBurdenRatio > 40) {
            $semaforo = 'amarillo';
            $mensajeSemaforo = 'Atención: Tu margen de utilidad es alto. Monitorea tus compras y gastos antes del cierre mensual.';
        }
        if ($taxBurdenRatio > 65 && $totalIncome > 10000) {
            $semaforo = 'rojo';
            $mensajeSemaforo = '¡Alerta Roja Fiscal! Tienes ingresos altos con muy pocas deducciones este mes. Te arriesgas a pagar muchos impuestos.';
        }
        if ($discrepancy > 0) {
            $semaforo = 'rojo';
            $mensajeSemaforo = 'RIESGO DE AUDITORÍA: Tienes $' . number_format($discrepancy, 2) . ' de retiros bancarios sin factura. El SAT puede detectar discrepancia fiscal.';
        }

        // 7. Alertas del Centro de Notificaciones
        $alerts = [];
        if ($missingInvoicesAmount > 0) {
            $alerts[] = [
                'title' => 'Fuga de Comprobantes',
                'description' => 'Tienes $' . number_format($missingInvoicesAmount, 2) . ' en costos fijos que pagaste pero no has facturado este mes.'
            ];
        }

        // 8. Facturas Recientes con contraparte correcta
        $recentInvoices = Invoice::where('company_id', $company->id)
            ->orderBy('issue_date', 'desc')
            ->take(5)
            ->get()
            ->map(function ($inv) {
                $partner = ($inv->type === 'I') 
                    ? ($inv->receiver_name ?: 'Público en general') 
                    : ($inv->issuer_name ?: 'Proveedor no identificado');

                return [
                    'id' => $inv->id,
                    'uuid' => $inv->uuid ?? substr((string)$inv->id, 0, 8),
                    'partner_name' => $partner,
                    'type' => $inv->type,
                    'total' => (float) $inv->total,
                    'issue_date' => \Carbon\Carbon::parse($inv->issue_date)->format('d/m/Y')
                ];
            });

        return response()->json([
            'company_name' => $company->trade_name ?? $company->legal_name,
            'selected_period' => $selectedPeriod,
            'semaforo' => $semaforo,
            'mensaje_semaforo' => $mensajeSemaforo,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'bank_withdrawals' => $bankWithdrawals,
            'total_payroll_gross' => (float) $totalPayrollGross,
            'discrepancy' => (float) $discrepancy,
            'missing_invoices_amount' => (float) $missingInvoicesAmount,
            'alerts' => $alerts,
            'recent_invoices' => $recentInvoices
        ]);
    }
}