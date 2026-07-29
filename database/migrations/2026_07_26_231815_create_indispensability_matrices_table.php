<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('indispensability_matrices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Llave foránea hacia un campo string (code)
            $table->string('sat_product_service_code', 8);
            $table->foreign('sat_product_service_code')
                ->references('code')
                ->on('sat_product_services');

            // Semáforo (Verde, Amarillo, Rojo)
            $table->enum('deductibility_status', ['DEDUCIBLE', 'RIESGO', 'NO_DEDUCIBLE']);

            $table->text('notes')->nullable();

            // Laravel maneja created_at y updated_at por defecto. Lo dejamos para mejores prácticas.
            $table->timestamps();

            // Restricción única para que una empresa no tenga reglas duplicadas para el mismo código
            $table->unique(['company_id', 'sat_product_service_code'], 'uq_company_prod_service');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indispensability_matrices');
    }
};
