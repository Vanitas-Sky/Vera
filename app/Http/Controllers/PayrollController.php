<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use App\Services\PayrollCalculatorService;
use App\Mail\PayslipEmail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollCalculatorService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Registra tu empresa primero.');
        }

        $period = $request->input('period');
        $query = \App\Models\PayrollPeriod::where('company_id', $company->id);

        if ($period) {
            $year = substr($period, 0, 4);
            $month = substr($period, 5, 2);
            $query->whereYear('start_date', $year)
                ->whereMonth('start_date', $month);
        }

        $periods = $query->orderBy('start_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('payrolls.index', compact('periods', 'period'));
    }

    public function generate(Request $request)
    {
        $company = Auth::user()->companies()->first();

        $employees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->with('error', 'No tienes empleados activos para calcular.');
        }

        $existingPeriod = PayrollPeriod::where('company_id', $company->id)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->first();

        if ($existingPeriod) {
            return redirect()->back()->with('error', 'Ya generaste la nómina de este mes. Si necesitas recalcular, primero debes cancelarla.');
        }

        DB::beginTransaction();

        try {
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

            $sumGross = 0;
            $sumIsr = 0;
            $sumImss = 0;
            $sumNet = 0;

            foreach ($employees as $employee) {
                // 1. Identificar la periodicidad del empleado
                $periodicity = $employee->periodicity ?? 'mensual';

                // 2. Definir el factor de consolidación mensual
                $factor = match ($periodicity) {
                    'quincenal' => 2,
                    'semanal' => 30.4 / 7, // Proporción exacta mensual (4.3428)
                    default => 1,
                };

                // 3. El servicio nos devuelve los cálculos de 1 SOLO PERIODO (1 quincena, 1 semana)
                $calculation = $this->payrollService->calculatePayroll($employee);

                // 4. Multiplicamos todos los rubros por el factor para tener el CONSOLIDADO MENSUAL
                $consolidatedGross = $calculation['gross_salary'] * $factor;
                $consolidatedIsr = $calculation['isr_retention'] * $factor;
                $consolidatedImss = $calculation['imss_retention'] * $factor;
                $consolidatedCustomTotal = $calculation['total_custom_deductions'] * $factor;
                $consolidatedNet = $calculation['net_salary'] * $factor;

                // Consolidar el array del detalle de deducciones
                $consolidatedCustomBreakdown = [];
                foreach ($calculation['custom_deductions'] as $cd) {
                    $consolidatedCustomBreakdown[] = [
                        'sat_key' => $cd['sat_key'],
                        'description' => $cd['description'],
                        'amount' => round($cd['amount'] * $factor, 2)
                    ];
                }

                // Guardar los datos mensuales consolidados en la Base de Datos
                PayrollDetail::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'gross_salary' => round($consolidatedGross, 2),
                    'isr_retention' => round($consolidatedIsr, 2),
                    'imss_employee' => round($consolidatedImss, 2),
                    'total_custom_deductions' => round($consolidatedCustomTotal, 2),
                    'custom_deductions_breakdown' => $consolidatedCustomBreakdown,
                    'net_salary' => round($consolidatedNet, 2),
                ]);

                $sumGross += $consolidatedGross;
                $sumIsr += $consolidatedIsr;
                $sumImss += $consolidatedImss;
                $sumNet += $consolidatedNet;
            }

            // Actualizar la carátula global del mes
            $period->update([
                'total_gross' => $sumGross,
                'total_isr_retention' => $sumIsr,
                'total_imss_employee' => $sumImss,
                'total_imss_employer' => 0,
                'total_net' => $sumNet,
            ]);

            DB::commit();

            return redirect()->route('payrolls.index')->with('success', 'Nómina calculada y guardada con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error crítico al calcular la nómina: ' . $e->getMessage());
        }
    }

    public function show($id, Request $request, PayrollCalculatorService $payrollService)
    {
        $company = Auth::user()->companies()->first();
        $search = $request->input('search');

        $period = PayrollPeriod::where('company_id', $company->id)
            ->with(['details' => function ($query) use ($search) {
                $query->with('employee');
                if ($search) {
                    $query->whereHas('employee', function ($q) use ($search) {
                        $q->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('rfc', 'LIKE', "%{$search}%");
                    });
                }
            }])
            ->findOrFail($id);

        foreach ($period->details as $detail) {
            $periodicity = $detail->employee->periodicity ?? 'mensual';
            $factor = match ($periodicity) {
                'quincenal' => 2,
                'semanal' => 30.4 / 7,
                default => 1,
            };

            // Salario Bruto de 1 solo periodo
            $periodGross = $detail->gross_salary / $factor;
            $detail->isr_breakdown = $payrollService->getIsrBreakdown($periodGross, $periodicity);

            $detail->periodicity_label = ucfirst($periodicity);
            $detail->multiplier = round($factor, 2);

            // NUEVO: Dinero real depositado en cada corte (Semana o Quincena)
            $detail->net_per_period = $detail->net_salary / $factor;
        }

        return view('payrolls.show', compact('period', 'search'));
    }

    public function destroy($id)
    {
        $company = Auth::user()->companies()->first();
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($id);

        try {
            DB::beginTransaction();
            $period->details()->delete();
            $period->delete();
            DB::commit();
            return redirect()->route('payrolls.index')->with('success', 'Nómina eliminada correctamente. El mes ha quedado liberado para un nuevo cálculo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al intentar eliminar la nómina: ' . $e->getMessage());
        }
    }

    public function downloadReceiptPdf($id, PayrollCalculatorService $payrollService)
    {
        $company = Auth::user()->companies()->first();

        $detail = PayrollDetail::whereHas('period', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->with(['employee', 'period'])->findOrFail($id);

        $periodicity = $detail->employee->periodicity ?? 'mensual';
        $factor = match ($periodicity) {
            'quincenal' => 2,
            'semanal' => 30.4 / 7,
            default => 1,
        };

        // Revertir el salario al periodo base para calcular correctamente los nodos del ISR
        $periodGross = $detail->gross_salary / $factor;
        $isrBreakdown = $payrollService->getIsrBreakdown($periodGross, $periodicity);

        $selloSimulado = "x8Yz9==";
        $urlSat = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=SIMULADO-NOMINA-{$detail->id}&re={$company->rfc}&rr={$detail->employee->rfc}";
        $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(0)->generate($urlSat));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payrolls.pdf', compact('company', 'detail', 'isrBreakdown', 'qrCode', 'periodicity', 'factor'));

        return $pdf->download('Recibo_Nomina_' . $detail->employee->rfc . '_' . $detail->period->start_date->format('mY') . '.pdf');
    }

    public function sendMassiveEmails($id, PayrollCalculatorService $payrollService)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($id);
        $details = PayrollDetail::with('employee')->where('payroll_period_id', $period->id)->get();

        $enviados = 0;
        $sinCorreo = 0;

        foreach ($details as $detail) {
            $employee = $detail->employee;

            if (!$employee->email) {
                $sinCorreo++;
                continue;
            }

            $periodicity = $employee->periodicity ?? 'mensual';
            $factor = match ($periodicity) {
                'quincenal' => 2,
                'semanal' => 30.4 / 7,
                default => 1,
            };

            $periodGross = $detail->gross_salary / $factor;
            $isrBreakdown = $payrollService->getIsrBreakdown($periodGross, $periodicity);

            $urlSat = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=SIMULADO-NOMINA-{$detail->id}&re={$company->rfc}&rr={$employee->rfc}";
            $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(0)->generate($urlSat));

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payrolls.pdf', compact('company', 'detail', 'isrBreakdown', 'qrCode', 'periodicity', 'factor'));
            $pdfContent = $pdf->output();

            Mail::to($employee->email)->send(new PayslipEmail($employee, $period->period_name, $pdfContent));
            $enviados++;
        }

        $mensaje = "Se enviaron $enviados recibos de nómina exitosamente.";
        if ($sinCorreo > 0) {
            $mensaje .= " ($sinCorreo empleados no tienen correo registrado).";
        }

        return redirect()->back()->with('success', $mensaje);
    }
}
