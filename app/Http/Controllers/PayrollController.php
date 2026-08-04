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

    /**
     * Inyección de Dependencias:
     * Laravel lee esto y automáticamente nos entrega una instancia de nuestro servicio matemático.
     */
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

        // 1. Recibir el parámetro (Formato HTML5: YYYY-MM)
        $period = $request->input('period');

        // 2. Query Builder
        $query = \App\Models\PayrollPeriod::where('company_id', $company->id);

        // 3. Aplicar filtro si el usuario seleccionó un mes
        if ($period) {
            $year = substr($period, 0, 4);
            $month = substr($period, 5, 2);

            // Asumiendo que el campo de fecha en tu tabla se llama start_date
            $query->whereYear('start_date', $year)
                ->whereMonth('start_date', $month);
        }

        // 4. Paginación
        $periods = $query->orderBy('start_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('payrolls.index', compact('periods', 'period'));
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
            $sumGross = 0;
            $sumIsr = 0;
            $sumImss = 0;
            $sumNet = 0;

            // 2. Calculamos los recibos y vamos sumando
            foreach ($employees as $employee) {
                // Ahora le pasamos el objeto $employee completo
                $calculation = $this->payrollService->calculatePayroll($employee);

                PayrollDetail::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'gross_salary' => $calculation['gross_salary'],
                    'isr_retention' => $calculation['isr_retention'],
                    'imss_employee' => $calculation['imss_retention'],
                    
                    // CONECTAMOS LAS DEDUCCIONES PERSONALIZADAS
                    'total_custom_deductions' => $calculation['total_custom_deductions'],
                    'custom_deductions_breakdown' => $calculation['custom_deductions'], 
                    
                    'net_salary' => $calculation['net_salary'],
                ]);

                $sumGross += $calculation['gross_salary'];
                $sumIsr += $calculation['isr_retention'];
                $sumImss += $calculation['imss_retention'];
                $sumNet += $calculation['net_salary'];
                // Nota: $sumNet ya viene con las deducciones restadas desde el servicio, así que la contabilidad global cuadrará perfecto.
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

    public function destroy($id)
    {
        $company = Auth::user()->companies()->first();

        // Aseguramos que el periodo pertenezca a la empresa actual
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($id);

        try {
            DB::beginTransaction();

            // 1. Borramos los detalles (recibos de los empleados)
            $period->details()->delete();

            // 2. Borramos el periodo central
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

        // Buscamos el recibo asegurándonos de que la nómina pertenezca a la empresa logueada
        $detail = PayrollDetail::whereHas('period', function ($query) use ($company) {
            $query->where('company_id', $company->id);
        })->with(['employee', 'period'])->findOrFail($id);

        // Calculamos el desglose para mostrar la retención en el PDF
        $isrBreakdown = $payrollService->getIsrBreakdown($detail->gross_salary);

        // Generamos un QR simulado (Igual que en las facturas)
        $selloSimulado = "x8Yz9==";
        $urlSat = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=SIMULADO-NOMINA-{$detail->id}&re={$company->rfc}&rr={$detail->employee->rfc}";
        $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(0)->generate($urlSat));

        // Cargamos la vista de dompdf
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payrolls.pdf', compact('company', 'detail', 'isrBreakdown', 'qrCode'));

        // Descargamos el archivo
        return $pdf->download('Recibo_Nomina_' . $detail->employee->rfc . '_' . $detail->period->start_date->format('mY') . '.pdf');
    }

    public function sendMassiveEmails($id, PayrollCalculatorService $payrollService)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();
        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($id);

        // Traemos todos los detalles de nómina de ese mes, con la data del empleado
        $details = PayrollDetail::with('employee')->where('payroll_period_id', $period->id)->get();

        $enviados = 0;
        $sinCorreo = 0;

        foreach ($details as $detail) {
            $employee = $detail->employee;

            // Si no tiene correo, lo saltamos
            if (!$employee->email) {
                $sinCorreo++;
                continue;
            }

            // 1. REGLA DE ORO: Construir los datos obligatorios para el PDF fiscal
            $isrBreakdown = $payrollService->getIsrBreakdown($detail->gross_salary);
            $urlSat = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=SIMULADO-NOMINA-{$detail->id}&re={$company->rfc}&rr={$employee->rfc}";
            $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(120)->margin(0)->generate($urlSat));

            // 2. Generar el PDF usando la vista CORRECTA (payrolls.pdf)
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payrolls.pdf', compact('company', 'detail', 'isrBreakdown', 'qrCode'));
            $pdfContent = $pdf->output(); // Genera el binario del PDF en memoria

            // 3. Enviar el correo (El Mailable ya sabe que debe usar emails.payslip para el mensaje)
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
