<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            
            // Clave del SAT (Ej: 009 para Infonavit, 007 para Pensión, 015 para Préstamos)
            $table->string('sat_key', 3); 
            
            // Descripción para el recibo (Ej: "Préstamo Caja de Ahorro" o "Crédito Infonavit 12345")
            $table->string('description'); 
            
            // REGLA DE ORO ARQUITECTÓNICA: ¿Cómo se calcula?
            // fixed = $500 fijos | percentage = 20% del salario | vsm = Factor Infonavit (UMI)
            $table->enum('amount_type', ['fixed', 'percentage', 'vsm']); 
            
            // Monto o Factor (Usamos 4 decimales porque el Infonavit calcula con fracciones muy precisas)
            $table->decimal('amount', 10, 4); 
            
            // Para poder pausar una deducción sin borrar su historial
            $table->boolean('is_active')->default(true); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
