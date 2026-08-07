<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Agregamos el Giro / Actividad Económica (nullable por si ya tienes empresas registradas)
            $table->string('industry', 150)->nullable()->after('tax_regime_code')->comment('Giro comercial de la empresa');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('industry');
        });
    }
};
