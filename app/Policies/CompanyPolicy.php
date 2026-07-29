<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * El usuario debe pertenecer a la empresa (o ser SUPER_ADMIN) para verla.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN
            || $user->companies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Cualquier usuario autenticado puede registrar una nueva empresa (PyME).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Solo ADMIN_PYME de esa empresa (o SUPER_ADMIN) puede actualizarla.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN
            || $user->userCompanies()
                ->where('company_id', $company->id)
                ->where('role_in_company', Role::ADMIN_PYME)
                ->exists();
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }
}
