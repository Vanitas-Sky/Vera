<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => Role::SUPER_ADMIN, 'description' => 'Acceso total a la plataforma y todas las empresas.'],
            ['name' => Role::ADMIN_PYME, 'description' => 'Administrador de la empresa (PyME) registrada.'],
            ['name' => Role::ACCOUNTANT, 'description' => 'Contador; puede modificar la Matriz de Indispensabilidad y calcular nominas.'],
            ['name' => Role::OPERATOR, 'description' => 'Capturista con acceso limitado a carga de informacion.'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
