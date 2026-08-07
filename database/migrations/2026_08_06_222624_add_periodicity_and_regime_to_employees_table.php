<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Periodicidad de pago (Por defecto todos los que ya tienes serán mensuales)
            $table->string('periodicity', 20)
                  ->default('mensual')
                  ->after('base_salary')
                  ->comment('mensual, quincenal, semanal');

            // Régimen de contratación SAT (Visual)
            $table->string('work_regime')
                  ->default('02 - Sueldos y Salarios')
                  ->after('periodicity')
                  ->comment('Ej: 02 - Sueldos y Salarios, 09 - Asimilados');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Si hacemos rollback, eliminamos las columnas de forma limpia
            $table->dropColumn(['periodicity', 'work_regime']);
        });
    }
};
