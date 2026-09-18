<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $employees = Employee::where('company_id', $company->id)
            ->select([
                'id',
                'full_name',
                'position',
                'email',
                'phone',
                'rfc',
                'curp',
                'nss',
                'clabe',
                'base_salary',
                'periodicity',
                'work_regime',
                'is_active'
            ])
            ->orderBy('full_name', 'asc')
            ->get();

        return response()->json($employees);
    }
}
