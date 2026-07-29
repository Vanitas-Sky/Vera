<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $company = Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Primero debes registrar tu empresa.');
        }

        // 1. Facturas de Ingreso (Ventas)
        $incomes = \App\Models\Invoice::where('company_id', $company->id)->where('type', 'I')->get();
        $totalIncome = $incomes->sum('total');
        $totalIncomeIva = $incomes->sum('iva');
        $subtotalIncome = $incomes->sum('subtotal');

        // 2. Facturas de Egreso (Gastos)
        $expenses = \App\Models\Invoice::where('company_id', $company->id)->where('type', 'E')->get();
        $totalExpense = $expenses->sum('total');
        $totalExpenseIva = $expenses->sum('iva');
        $subtotalExpense = $expenses->sum('subtotal');

        // 3. Nóminas (Operación)
        $payrolls = \App\Models\PayrollPeriod::where('company_id', $company->id)->get();
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
        $mensajeSemaforo = 'Tu balance financiero y deducciones se encuentran en un nivel saludable.';

        if ($taxBurdenRatio > 65 && $totalIncome > 10000) {
            $semaforo = 'rojo';
            $mensajeSemaforo = '¡Alerta Roja Fiscal! Tienes ingresos altos con muy pocas deducciones. Te arriesgas a pagar una cantidad elevada de impuestos.';
        } elseif ($taxBurdenRatio > 40) {
            $semaforo = 'amarillo';
            $mensajeSemaforo = 'Atención: Tu margen de utilidad es alto. Monitorea tus compras y gastos antes del cierre mensual.';
        }

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
            'subtotalExpense'
        ));
    }
}
