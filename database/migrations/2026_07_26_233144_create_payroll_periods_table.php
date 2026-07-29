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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('period_name', 100);
            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('total_gross', 12, 2);
            $table->decimal('total_isr_retention', 12, 2);
            $table->decimal('total_imss_employee', 12, 2);
            $table->decimal('total_imss_employer', 12, 2);
            $table->decimal('total_net', 12, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
