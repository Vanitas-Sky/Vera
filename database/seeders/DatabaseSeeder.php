<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            // 1. Roles y Catálogos SAT (No dependen de nadie)
            RoleSeeder::class,
            SatTaxRegimeSeeder::class,
            SatPaymentMethodSeeder::class,
            SatPaymentFormSeeder::class,
            SatCfdiUseSeeder::class,
            SatEconomicActivitySeeder::class,
            SatProductServiceSeeder::class,

            // 2. Usuarios y Empresas (Dependen de Roles y Regímenes Fiscales)
            UserSeeder::class,
            CompanySeeder::class,

            // 3. Reglas de Negocio (Dependen de Empresas, Giros y Productos)
            CompanyEconomicActivitySeeder::class,
            IndispensabilityMatrixSeeder::class,
        ]);
    }
}
