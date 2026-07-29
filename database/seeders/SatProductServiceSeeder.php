<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatProductServiceSeeder extends Seeder
{
    public function run()
    {
        $products = [
            // Ideal para Semáforo VERDE (Giros de Software)
            ['code' => '81111500', 'description' => 'Servicios de ingeniería de software o hardware', 'similar_words' => 'desarrollo, programacion, codigo, app, web'],
            ['code' => '43211500', 'description' => 'Computadoras', 'similar_words' => 'pc, laptop, ordenador, macbook, equipo'],
            
            // Ideal para Semáforo AMARILLO (Requiere NLP/Revisión)
            ['code' => '90101500', 'description' => 'Establecimientos para comer y beber', 'similar_words' => 'restaurante, viaticos, comida, cena, consumo'],
            
            // Ideal para Semáforo ROJO (No deducible para software)
            ['code' => '50202300', 'description' => 'Bebidas no alcohólicas', 'similar_words' => 'agua, refresco, despensa, supermercado'],
            ['code' => '53101500', 'description' => 'Prendas de vestir', 'similar_words' => 'ropa, camisa, pantalon, zapatos'],
        ];

        DB::table('sat_product_services')->insert($products);
    }
}
