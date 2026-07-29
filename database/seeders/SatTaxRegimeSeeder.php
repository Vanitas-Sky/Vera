<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatTaxRegimeSeeder extends Seeder
{
    public function run()
    {
        $regimes = [
            ['code' => '601', 'description' => 'General de Ley Personas Morales'],
            ['code' => '612', 'description' => 'Personas Físicas con Actividades Empresariales y Profesionales'],
            ['code' => '626', 'description' => 'Régimen Simplificado de Confianza (RESICO)'],
        ];

        DB::table('sat_tax_regimes')->insert($regimes);
    }
}
