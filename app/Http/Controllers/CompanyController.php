<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Company::class, 'company');
    }

    /**
     * Display a listing of the resource (empresas del usuario autenticado).
     */
    public function index()
    {
        return Auth::user()->companies()->paginate();
    }

    /**
     * Store a newly created resource in storage y vincula al usuario creador como ADMIN_PYME.
     */
    public function store(StoreCompanyRequest $request)
    {
        $company = Company::query()->create($request->validated());

        $company->userCompanies()->create([
            'user_id' => Auth::id(),
            'role_in_company' => Role::ADMIN_PYME,
        ]);

        return response()->json($company, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        return $company;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return response()->json($company);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return response()->noContent();
    }
}
