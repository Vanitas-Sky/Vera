<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Usuario 1: Super Administrador
        User::create([
            'role_id' => 1,
            'email' => 'admin@vera.ai',
            'password' => Hash::make('password123'),
            'name' => 'Super Admin Vera',
            'is_active' => true,
        ]);

        // Usuario 2: Contador de pruebas
        User::create([
            'role_id' => 3,
            'email' => 'contador@despacho.com',
            'password' => Hash::make('password123'),
            'name' => 'Contador Pruebas',
            'is_active' => true,
        ]);

        // 3. Auditor Externo de pruebas
        $auditorRole = Role::where('name', Role::AUDITOR_EXTERNO)->first();
        $auditorUser = User::firstOrCreate(
            ['email' => 'auditor@despacho.com'],
            [
                'role_id' => $auditorRole->id,
                'name' => 'Auditor Fiscal Externo',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Vincular el auditor a la primera empresa existente para que no lo desvíe el Onboarding
        $firstCompany = Company::first();
        if ($firstCompany && !$auditorUser->companies()->where('company_id', $firstCompany->id)->exists()) {
            $auditorUser->companies()->attach($firstCompany->id, ['role_in_company' => 'AUDITOR']);
        }
    }
}
