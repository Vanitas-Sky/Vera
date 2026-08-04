<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Primero debes registrar tu empresa.');
        }

        $selectedPeriod = $request->input('period', now()->format('Y-m'));
        $currentYear = (int) substr($selectedPeriod, 0, 4);
        $currentMonth = (int) substr($selectedPeriod, 5, 2);

        // 1. Facturas de Ingreso
        $incomes = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'I')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->get();

        $totalIncome = $incomes->sum('total');
        $totalIncomeIva = $incomes->sum('iva');
        $subtotalIncome = $incomes->sum('subtotal');

        // 2. Facturas de Egreso
        $expenses = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'E')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->get();

        $totalExpense = $expenses->sum('total');
        $totalExpenseIva = $expenses->sum('iva');
        $subtotalExpense = $expenses->sum('subtotal');

        // 3. Banco y Discrepancia
        $bankWithdrawals = \App\Models\BankTransaction::where('company_id', $company->id)
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('withdrawal');

        $discrepancy = $bankWithdrawals - $totalExpense;

        // 4. Nóminas (ACTUALIZADO CON IMSS)
       $payrolls = \App\Models\PayrollPeriod::where('company_id', $company->id)
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->with('details') // <-- Asegúrate de cargar los recibos
            ->get();

        $totalPayrollGross = $payrolls->sum('total_gross');
        $totalPayrollNet = $payrolls->sum('total_net');
        $totalIsrRetained = $payrolls->sum('total_isr_retention');
        $totalImssRetained = $payrolls->sum('total_imss_employee');
        
        // Sumamos todas las deducciones personalizadas de todos los recibos del mes
        $totalCustomDeductions = $payrolls->flatMap->details->sum('total_custom_deductions');

        // 5. Proyección OpEx
        $projectedOpex = \App\Models\FixedExpense::where('company_id', $company->id)
            ->where('is_active', true)
            ->sum('monthly_amount');

        $missingInvoicesAmount = 0;
        if ($projectedOpex > $subtotalExpense) {
            $missingInvoicesAmount = $projectedOpex - $subtotalExpense;
        }

        // 6. Cálculos Fiscales Clave
        $netIva = $totalIncomeIva - $totalExpenseIva;
        $netProfit = $subtotalIncome - $subtotalExpense - $totalPayrollGross;

        // ==========================================
        // 7. LÓGICA DEL SEMÁFORO (CORREGIDA EN CASCADA)
        // ==========================================
        $taxBurdenRatio = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Estado base
        $semaforo = 'verde';
        $mensajeSemaforo = 'Tu balance financiero y deducciones se encuentran en un nivel saludable para este mes.';

        // Evaluamos primer riesgo: Carga fiscal por falta de gastos
        if ($taxBurdenRatio > 40) {
            $semaforo = 'amarillo';
            $mensajeSemaforo = 'Atención: Tu margen de utilidad es alto. Monitorea tus compras y gastos antes del cierre mensual.';
        }
        if ($taxBurdenRatio > 65 && $totalIncome > 10000) {
            $semaforo = 'rojo';
            $mensajeSemaforo = '¡Alerta Roja Fiscal! Tienes ingresos altos con muy pocas deducciones este mes. Te arriesgas a pagar muchos impuestos.';
        }

        // Evaluamos riesgo crítico: Discrepancia Fiscal (Este sobrescribe y mata a los anteriores)
        if ($discrepancy > 0) {
            $semaforo = 'rojo';
            $mensajeSemaforo = 'RIESGO DE AUDITORÍA: Tienes $' . number_format($discrepancy, 2) . ' de retiros bancarios sin factura. El SAT puede detectar discrepancia fiscal.';
        }

        // 8. Historial de facturas
        $invoices = \App\Models\Invoice::where('company_id', $company->id)
            ->orderBy('issue_date', 'desc')
            ->get();

        $alerts = [];

        // Alerta A: Vencimientos de Contratos (OpEx)
        $expiringContracts = \App\Models\FixedExpense::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<=', now()->addDays(30))
            ->get();

        foreach ($expiringContracts as $contract) {
            $daysLeft = (int) now()->diffInDays($contract->contract_end_date, false);

            if ($daysLeft < 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    'title' => 'Contrato Vencido',
                    'message' => "El contrato de {$contract->provider_name} ({$contract->category}) venció hace " . abs($daysLeft) . " días."
                ];
            } else {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => 'Vencimiento Próximo',
                    'message' => "La póliza/contrato de {$contract->provider_name} vence en {$daysLeft} días."
                ];
            }
        }

        // Alerta B: Fuga de Comprobantes OpEx
        if ($missingInvoicesAmount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'title' => 'Fuga de Comprobantes',
                'message' => "Tienes $" . number_format($missingInvoicesAmount, 2) . " en costos fijos que pagaste pero no has facturado este mes."
            ];
        }

        // Alerta C: Si no hay nada de qué preocuparse
        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'title' => 'Todo en orden',
                'message' => 'No tienes contratos por vencer ni anomalías operativas pendientes.'
            ];
        }

        return view('dashboard', compact(
            'totalIncome',
            'totalIncomeIva',
            'totalExpense',
            'totalExpenseIva',
            'totalPayrollGross',
            'totalPayrollNet',
            'totalIsrRetained',
            'totalImssRetained',
            'totalCustomDeductions',
            'netIva',
            'netProfit',
            'semaforo',
            'mensajeSemaforo',
            'projectedOpex',
            'missingInvoicesAmount',
            'subtotalExpense',
            'invoices',
            'selectedPeriod',
            'bankWithdrawals',
            'discrepancy',
            'alerts'
        ));
    }
}
