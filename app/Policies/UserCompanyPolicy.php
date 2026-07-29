<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\UserCompany;

class UserCompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN || $user->userCompanies()->exists();
    }

    public function view(User $user, UserCompany $userCompany): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN || $user->id === $userCompany->user_id;
    }

    /**
     * Solo un ADMIN_PYME de la empresa destino (o SUPER_ADMIN) puede vincular nuevos usuarios.
     */
    public function create(User $user): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN
            || $user->userCompanies()->where('role_in_company', Role::ADMIN_PYME)->exists();
    }

    public function update(User $user, UserCompany $userCompany): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN
            || $user->userCompanies()
                ->where('company_id', $userCompany->company_id)
                ->where('role_in_company', Role::ADMIN_PYME)
                ->exists();
    }

    public function delete(User $user, UserCompany $userCompany): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN
            || $user->userCompanies()
                ->where('company_id', $userCompany->company_id)
                ->where('role_in_company', Role::ADMIN_PYME)
                ->exists();
    }

    public function restore(User $user, UserCompany $userCompany): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function forceDelete(User $user, UserCompany $userCompany): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }
}
