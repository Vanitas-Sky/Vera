<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            // Agregamos las columnas después de imss_employee para mantener orden
            $table->decimal('total_custom_deductions', 10, 2)->default(0)->after('imss_employee');
            $table->json('custom_deductions_breakdown')->nullable()->after('total_custom_deductions');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropColumn(['total_custom_deductions', 'custom_deductions_breakdown']);
        });
    }
};
