<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PayrollPeriod;
use App\Models\PayrollDetail;
use Barryvdh\DomPDF\Facade\Pdf;


class PayrollApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $periods = PayrollPeriod::where('company_id', $company->id)
            ->withCount('details')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($periods);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $period = PayrollPeriod::where('company_id', $company->id)->findOrFail($id);

        // Cargar los detalles con los datos del empleado asociado
        $details = PayrollDetail::where('payroll_period_id', $period->id)
            ->with(['employee' => function ($query) {
                $query->select('id', 'full_name', 'rfc', 'position', 'periodicity', 'nss', 'work_regime');
            }])
            ->get();

        return response()->json([
            'period' => $period,
            'details' => $details
        ]);
    }

    public function downloadReceiptPdf(Request $request, $periodId, $employeeId)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $detail = PayrollDetail::where('payroll_period_id', $periodId)
            ->where('employee_id', $employeeId)
            ->with(['employee', 'period'])
            ->firstOrFail();

        // Reutiliza la misma vista Blade que ya tienes para los recibos en la web
        $pdf = Pdf::loadView('payrolls.pdf', compact('detail', 'company'));

        $fileName = "Recibo_{$detail->employee->rfc}_{$detail->period->id}.pdf";

        return $pdf->download($fileName);
    }
}
