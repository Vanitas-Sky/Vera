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

        // 1. Atrapamos el periodo enviado por la vista, o usamos el actual por defecto
        // El input type="month" envía el formato "YYYY-MM" (ej. "2026-07")
        $selectedPeriod = $request->input('period', now()->format('Y-m'));

        // Separamos el año y el mes para nuestras consultas
        $currentYear = (int) substr($selectedPeriod, 0, 4);
        $currentMonth = (int) substr($selectedPeriod, 5, 2);

        // 1. Facturas de Ingreso (Ventas) -> Solo MES ACTUAL y NO CANCELADAS
        $incomes = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'I')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->get();

        $totalIncome = $incomes->sum('total');
        $totalIncomeIva = $incomes->sum('iva');
        $subtotalIncome = $incomes->sum('subtotal');

        // 2. Facturas de Egreso (Gastos) -> Solo MES ACTUAL y NO CANCELADAS
        $expenses = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'E')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->get();

        $totalExpense = $expenses->sum('total');
        $totalExpenseIva = $expenses->sum('iva');
        $subtotalExpense = $expenses->sum('subtotal');

        // 3. Nóminas (Operación) -> Solo MES ACTUAL
        $payrolls = \App\Models\PayrollPeriod::where('company_id', $company->id)
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->get();

        $totalPayrollGross = $payrolls->sum('total_gross');
        $totalPayrollNet = $payrolls->sum('total_net');
        $totalIsrRetained = $payrolls->sum('total_isr_retention');

        // 4. Proyección OpEx vs Realidad
        $projectedOpex = \App\Models\FixedExpense::where('company_id', $company->id)
            ->where('is_active', true)
            ->sum('monthly_amount');

        // Comparamos el subtotal facturado contra la proyección antes de IVA
        $missingInvoicesAmount = 0;
        if ($projectedOpex > $subtotalExpense) {
            $missingInvoicesAmount = $projectedOpex - $subtotalExpense;
        }

        // 5. Cálculos Fiscales Clave
        $netIva = $totalIncomeIva - $totalExpenseIva;
        $netProfit = $subtotalIncome - $subtotalExpense - $totalPayrollGross;

        // 6. Lógica del Semáforo Fiscal
        $taxBurdenRatio = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        $semaforo = 'verde';
        $mensajeSemaforo = 'Tu balance financiero y deducciones se encuentran en un nivel saludable para este mes.';

        if ($taxBurdenRatio > 65 && $totalIncome > 10000) {
            $semaforo = 'rojo';
            $mensajeSemaforo = '¡Alerta Roja Fiscal! Tienes ingresos altos con muy pocas deducciones este mes. Te arriesgas a pagar muchos impuestos.';
        } elseif ($taxBurdenRatio > 40) {
            $semaforo = 'amarillo';
            $mensajeSemaforo = 'Atención: Tu margen de utilidad es alto. Monitorea tus compras y gastos antes del cierre mensual.';
        }

        // 7. Historial para la tabla (Aquí traemos todas para ver el registro, no solo las del mes)
        // CORRECCIÓN: Agregué esta consulta que te faltaba para que la tabla no salga vacía.
        $invoices = \App\Models\Invoice::where('company_id', $company->id)
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('dashboard', compact(
            'totalIncome',
            'totalIncomeIva',
            'totalExpense',
            'totalExpenseIva',
            'totalPayrollGross',
            'totalPayrollNet',
            'totalIsrRetained',
            'netIva',
            'netProfit',
            'semaforo',
            'mensajeSemaforo',
            'projectedOpex',
            'missingInvoicesAmount',
            'subtotalExpense',
            'invoices', // Variable reincorporada
            'selectedPeriod'
        ));
    }
}
