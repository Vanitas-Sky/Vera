<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FixedExpense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FixedExpenseApiController extends Controller
{
    private function normalizeCategory(string $category): string
    {
        $cat = strtoupper(trim($category));
        
        return match ($cat) {
            'RENTA', 'RENTAS'             => 'RENTA',
            'SEGURO', 'SEGUROS'           => 'SEGURO',
            'SERVICIO', 'SERVICIOS'       => 'SERVICIO',
            default                       => 'OTRO',
        };
    }

    public function index(Request $request)
    {
        $company = $request->user()->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada.'], 404);
        }

        $expenses = FixedExpense::where('company_id', $company->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('due_day', 'asc')
            ->get();

        $activeExpenses = $expenses->where('is_active', true);
        $totalMonthlyOpex = (float) $activeExpenses->sum('monthly_amount');
        $annualProjection = (float) ($totalMonthlyOpex * 12);
        $activeContractsCount = $activeExpenses->count();

        $alerts = [];
        $now = Carbon::now();

        foreach ($activeExpenses as $expense) {
            if ($expense->contract_end_date) {
                $endDate = Carbon::parse($expense->contract_end_date);
                if ($endDate->isPast()) {
                    $alerts[] = [
                        'type'    => 'danger',
                        'message' => "El contrato con {$expense->provider_name} expiró el {$endDate->format('d/m/Y')}.",
                        'action'  => 'VENCIDO'
                    ];
                } elseif ($endDate->diffInDays($now) <= 30) {
                    $alerts[] = [
                        'type'    => 'warning',
                        'message' => "El contrato con {$expense->provider_name} vence en {$endDate->diffInDays($now)} días.",
                        'action'  => 'POR VENCER'
                    ];
                }
            }
        }

        $formattedExpenses = $expenses->map(function ($e) {
            return [
                'id'                  => $e->id,
                'provider_name'       => $e->provider_name,
                'description'         => $e->description ?? '',
                'category'            => $e->category,
                'monthly_amount'      => (float) $e->monthly_amount,
                'due_day'             => (int) $e->due_day,
                'contract_start_date' => $e->contract_start_date ? Carbon::parse($e->contract_start_date)->format('Y-m-d') : null,
                'contract_end_date'   => $e->contract_end_date ? Carbon::parse($e->contract_end_date)->format('Y-m-d') : null,
                'is_active'           => (bool) $e->is_active,
            ];
        });

        return response()->json([
            'total_monthly_opex'     => $totalMonthlyOpex,
            'annual_projection'      => $annualProjection,
            'active_contracts_count' => $activeContractsCount,
            'alerts'                 => $alerts,
            'expenses'               => $formattedExpenses,
        ], 200);
    }

    public function store(Request $request)
    {
        $company = $request->user()->companies()->firstOrFail();

        $validated = $request->validate([
            'provider_name'       => 'required|string|max:150',
            'description'         => 'nullable|string',
            'category'            => 'required|string',
            'monthly_amount'      => 'required|numeric|min:0.01',
            'due_day'             => 'required|integer|between:1,31',
            'contract_start_date' => 'nullable|date',
            'contract_end_date'   => 'nullable|date|after_or_equal:contract_start_date',
        ]);

        // Mapeo seguro al ENUM de MySQL
        $validated['category'] = $this->normalizeCategory($validated['category']);

        FixedExpense::create(array_merge($validated, [
            'company_id' => $company->id,
            'is_active'  => true,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Contrato OpEx registrado correctamente.'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $company = $request->user()->companies()->firstOrFail();
        $expense = FixedExpense::where('company_id', $company->id)->findOrFail($id);

        $validated = $request->validate([
            'provider_name'       => 'required|string|max:150',
            'description'         => 'nullable|string',
            'category'            => 'required|string',
            'monthly_amount'      => 'required|numeric|min:0.01',
            'due_day'             => 'required|integer|between:1,31',
            'contract_start_date' => 'nullable|date',
            'contract_end_date'   => 'nullable|date|after_or_equal:contract_start_date',
        ]);

        $validated['category'] = $this->normalizeCategory($validated['category']);

        $expense->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contrato actualizado exitosamente.'
        ], 200);
    }

    public function toggle(Request $request, $id)
    {
        $company = $request->user()->companies()->firstOrFail();
        $expense = FixedExpense::where('company_id', $company->id)->findOrFail($id);

        $expense->update([
            'is_active' => !$expense->is_active
        ]);

        $statusStr = $expense->is_active ? 'reactivado' : 'dado de baja';

        return response()->json([
            'success' => true,
            'message' => "El contrato ha sido {$statusStr} exitosamente."
        ], 200);
    }
}