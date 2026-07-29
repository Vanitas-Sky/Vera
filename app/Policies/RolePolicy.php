<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Solo el SUPER_ADMIN administra los roles del sistema.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function view(User $user, Role $role): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function update(User $user, Role $role): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->role?->name === Role::SUPER_ADMIN;
    }
}
