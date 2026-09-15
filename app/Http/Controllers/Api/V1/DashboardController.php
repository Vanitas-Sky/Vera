<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankTransaction;
use App\Models\Invoice;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        
        // Obtiene la empresa asociada al usuario (ej. ID 1 o la que tenga asignada)
        $company = $user->companies()->first(); 

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        // Consultas utilizando los nombres reales de las columnas en tu base de datos
        $totalBankWithdrawals = BankTransaction::where('company_id', $company->id)->sum('withdrawal');
        $totalInvoicedExpenses = Invoice::where('company_id', $company->id)->sum('total');
        
        $fiscalDiscrepancy = max(0, $totalBankWithdrawals - $totalInvoicedExpenses);
        $hasRisk = $fiscalDiscrepancy > 0;

        return response()->json([
            'companyName' => $company->legal_name ?? 'Mi PyME',
            'totalBankWithdrawals' => (float) $totalBankWithdrawals,
            'totalInvoicedExpenses' => (float) $totalInvoicedExpenses,
            'fiscalDiscrepancy' => (float) $fiscalDiscrepancy,
            'hasRisk' => $hasRisk,
        ]);
    }
}