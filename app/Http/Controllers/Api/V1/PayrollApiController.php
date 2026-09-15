<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PayrollPeriod;

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
}