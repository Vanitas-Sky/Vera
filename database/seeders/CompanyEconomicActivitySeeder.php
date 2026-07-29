<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyEconomicActivitySeeder extends Seeder
{
    public function run()
    {
        DB::table('company_economic_activities')->insert([
            'company_id' => 1,
            'sat_economic_activity_id' => 1, // 541510 - Servicios de diseño de sistemas de cómputo
        ]);
    }
}