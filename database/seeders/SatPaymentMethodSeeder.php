<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatPaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            ['code' => 'PUE', 'description' => 'Pago en una sola exhibición'],
            ['code' => 'PPD', 'description' => 'Pago en parcialidades o diferido'],
        ];

        DB::table('sat_payment_methods')->insert($methods);
    }
}