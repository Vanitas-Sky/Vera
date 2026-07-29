<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IndispensabilityMatrixSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $rules = [
            // VERDE: Completamente deducible para una empresa de software
            [
                'company_id' => 1,
                'sat_product_service_code' => '81111500', // Ingeniería de software
                'deductibility_status' => 'DEDUCIBLE',
                'notes' => 'Gasto fundamental para la operación del negocio.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'sat_product_service_code' => '43211500', // Computadoras
                'deductibility_status' => 'DEDUCIBLE',
                'notes' => 'Herramienta de trabajo estrictamente indispensable.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            
            // AMARILLO: En riesgo (Requiere auditoría o evaluación NLP)
            [
                'company_id' => 1,
                'sat_product_service_code' => '90101500', // Restaurantes
                'deductibility_status' => 'RIESGO',
                'notes' => 'Evaluar si fue viático (a más de 50km) o gasto de representación.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            
            // ROJO: No deducible para este giro
            [
                'company_id' => 1,
                'sat_product_service_code' => '50202300', // Bebidas/Despensa
                'deductibility_status' => 'NO_DEDUCIBLE',
                'notes' => 'Gasto personal inconsistente con el giro tecnológico.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'sat_product_service_code' => '53101500', // Ropa
                'deductibility_status' => 'NO_DEDUCIBLE',
                'notes' => 'Vestimenta no catalogada como uniforme oficial.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('indispensability_matrices')->insert($rules);
    }
}
