<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatCfdiUseSeeder extends Seeder
{
    public function run()
    {
        $uses = [
            ['code' => 'G01', 'description' => 'Adquisición de mercancías'],
            ['code' => 'G03', 'description' => 'Gastos en general'],
            ['code' => 'I04', 'description' => 'Equipo de computo y accesorios'],
            ['code' => 'I08', 'description' => 'Otra maquinaria y equipo'],
            ['code' => 'P01', 'description' => 'Por definir'],
        ];

        DB::table('sat_cfdi_uses')->insert($uses);
    }
}
