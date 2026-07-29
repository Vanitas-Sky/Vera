<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatEconomicActivitySeeder extends Seeder
{
    public function run()
    {
        $activities = [
            ['code' => '541510', 'name' => 'Servicios de diseño de sistemas de cómputo y servicios relacionados', 'description' => 'Desarrollo de software y consultoría IT.'],
            ['code' => '541211', 'name' => 'Servicios de contabilidad y auditoría', 'description' => 'Despachos contables.'],
            ['code' => '722510', 'name' => 'Servicios de preparación de alimentos y bebidas', 'description' => 'Restaurantes y cafeterías.'],
        ];

        DB::table('sat_economic_activities')->insert($activities);
    }
}
