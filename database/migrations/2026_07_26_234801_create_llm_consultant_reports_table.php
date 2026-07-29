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
        Schema::create('llm_consultant_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->integer('period_year');
            $table->integer('period_month');

            $table->text('executive_summary');

            // Columna JSON nativa para las estrategias tácticas generadas por el LLM
            $table->json('recommendations_json');

            $table->timestamp('generated_at')->useCurrent();

            // Restricción única para generar un solo reporte por mes/año
            $table->unique(['company_id', 'period_year', 'period_month'], 'uq_company_period_llm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_consultant_reports');
    }
};
