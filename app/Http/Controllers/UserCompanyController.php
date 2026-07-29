<?php

namespace App\Http\Controllers;

use App\Models\UserCompany;
use App\Http\Requests\StoreUserCompanyRequest;
use App\Http\Requests\UpdateUserCompanyRequest;

class UserCompanyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(UserCompany::class, 'userCompany');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserCompany::query()->with(['user', 'company'])->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserCompanyRequest $request)
    {
        $userCompany = UserCompany::query()->create($request->validated());

        return response()->json($userCompany, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserCompany $userCompany)
    {
        return $userCompany->load(['user', 'company']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserCompanyRequest $request, UserCompany $userCompany)
    {
        $userCompany->update($request->validated());

        return response()->json($userCompany);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserCompany $userCompany)
    {
        $userCompany->delete();

        return response()->noContent();
    }
}
