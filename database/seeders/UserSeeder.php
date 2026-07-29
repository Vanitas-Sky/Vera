<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Usuario 1: Super Administrador
        User::create([
            'role_id' => 1, 
            'email' => 'admin@vera.ai',
            'password' => Hash::make('password123'),
            'name' => 'Super Admin Vera',
            'is_active' => true,
        ]);

        // Usuario 2: Contador de pruebas
        User::create([
            'role_id' => 3, 
            'email' => 'contador@despacho.com',
            'password' => Hash::make('password123'),
            'name' => 'Contador Pruebas',
            'is_active' => true,
        ]);
    }
}