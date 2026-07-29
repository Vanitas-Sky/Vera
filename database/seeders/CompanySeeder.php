<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    public function run()
    {
        // 1. Crear la empresa de prueba usando el RFC genérico del SAT
        $company = Company::create([
            'rfc' => 'EKU9003173C9', 
            'legal_name' => 'Vera Tech S.A. de C.V.',
            'trade_name' => 'Vera AI',
            'postal_code' => '29200',
            'tax_regime_code' => '601', // General de Ley Personas Morales
        ]);

        // 2. Llenar la tabla pivote: Asignar el contador (User ID 2) a esta empresa
        DB::table('user_companies')->insert([
            'user_id' => 2,
            'company_id' => $company->id,
            'role_in_company' => 'ACCOUNTANT'
        ]);
    }
}
