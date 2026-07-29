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
        Schema::create('risk_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->integer('period_year');
            $table->integer('period_month');

            // unsignedTinyInteger es perfecto para un rango de 1 a 100
            $table->unsignedTinyInteger('risk_score');

            // Columna JSON nativa para las anomalías del Machine Learning
            $table->json('anomalies_detected_json')->nullable();

            $table->timestamp('evaluated_at')->useCurrent();

            // Restricción única para tener solo un score de riesgo por mes/año
            $table->unique(['company_id', 'period_year', 'period_month'], 'uq_company_period_risk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_evaluations');
    }
};
