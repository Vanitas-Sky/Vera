<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatPaymentFormSeeder extends Seeder
{
    public function run()
    {
        $forms = [
            ['code' => '01', 'description' => 'Efectivo'],
            ['code' => '03', 'description' => 'Transferencia electrónica de fondos'],
            ['code' => '04', 'description' => 'Tarjeta de crédito'],
            ['code' => '28', 'description' => 'Tarjeta de débito'],
            ['code' => '99', 'description' => 'Por definir'],
        ];

        DB::table('sat_payment_forms')->insert($forms);
    }
}
