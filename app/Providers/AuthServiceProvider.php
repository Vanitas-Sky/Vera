<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Role;
use App\Models\UserCompany;
use App\Policies\CompanyPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserCompanyPolicy;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Role::class => RolePolicy::class,
        Company::class => CompanyPolicy::class,
        UserCompany::class => UserCompanyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
