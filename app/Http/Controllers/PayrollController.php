<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Services\PayrollCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    protected $payrollService;

    /**
     * Inyección de Dependencias:
     * Laravel lee esto y automáticamente nos entrega una instancia de nuestro servicio matemático.
     */
    public function __construct(PayrollCalculatorService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index()
    {
        $company = Auth::user()->companies()->first();

        // Traemos los periodos de nómina calculados, ordenados del más reciente al más antiguo
        $periods = PayrollPeriod::where('company_id', $company->id)->latest()->get();

        return view('payrolls.index', compact('periods'));
    }

    public function generate(Request $request)
    {
        $company = Auth::user()->companies()->first();

        // 1. Obtener empleados activos
        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'No tienes empleados activos para calcular.');
        }

        // Evitar duplicidad: Revisar si ya existe una nómina calculada este mes
        $existingPeriod = PayrollPeriod::where('company_id', $company->id)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->first();

        if ($existingPeriod) {
            return redirect()->back()->with('error', 'Ya generaste la nómina de este mes. Si necesitas recalcular, primero debes cancelarla.');
        }

        // 2. Iniciamos el blindaje de la base de datos
        DB::beginTransaction();

       try {
            // 1. Creamos el periodo (Inicializando los totales en 0 para evitar error de nulos)
            $periodName = 'Nómina - ' . ucfirst(now()->translatedFormat('F Y'));

            $period = PayrollPeriod::create([
                'company_id' => $company->id,
                'period_name' => $periodName,
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'total_gross' => 0,
                'total_isr_retention' => 0,
                'total_imss_employee' => 0,
                'total_imss_employer' => 0,
                'total_net' => 0,
            ]);

            // Variables para acumular el dinero
            $sumGross = 0; $sumIsr = 0; $sumImss = 0; $sumNet = 0;

            // 2. Calculamos los recibos y vamos sumando
            foreach ($employees as $employee) {
                $calculation = $this->payrollService->calculatePayroll($employee->base_salary);

                PayrollDetail::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'gross_salary' => $calculation['gross_salary'],
                    'isr_retention' => $calculation['isr_retention'],
                    'imss_employee' => $calculation['imss_retention'],
                    'net_salary' => $calculation['net_salary'],
                ]);

                $sumGross += $calculation['gross_salary'];
                $sumIsr += $calculation['isr_retention'];
                $sumImss += $calculation['imss_retention'];
                $sumNet += $calculation['net_salary'];
            }

            // 3. Actualizamos el periodo central con los totales reales
            $period->update([
                'total_gross' => $sumGross,
                'total_isr_retention' => $sumIsr,
                'total_imss_employee' => $sumImss,
                'total_imss_employer' => 0, // Aún no calculamos cuotas patronales
                'total_net' => $sumNet,
            ]);

            // 4. Si todo salió perfecto, guardamos permanentemente en la base de datos
            DB::commit();

            return redirect()->route('payrolls.index')->with('success', 'Nómina calculada y guardada con éxito.');
        } catch (\Exception $e) {
            // Si algo falla, revertimos absolutamente todo para no corromper los datos
            DB::rollBack();
            return redirect()->back()->with('error', 'Error crítico al calcular la nómina: ' . $e->getMessage());
        }
    }

    public function show($id, PayrollCalculatorService $payrollService)
    {
        $company = Auth::user()->companies()->first();
        
        $period = PayrollPeriod::where('company_id', $company->id)
                               ->with(['details.employee'])
                               ->findOrFail($id);

        // Inyectamos la memoria de cálculo dinámicamente a cada recibo
        foreach ($period->details as $detail) {
            $detail->isr_breakdown = $payrollService->getIsrBreakdown($detail->gross_salary);
        }

        return view('payrolls.show', compact('period'));
    }
}
