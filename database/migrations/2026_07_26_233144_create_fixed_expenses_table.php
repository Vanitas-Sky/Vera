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
        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('provider_name', 150);
            $table->enum('category', ['RENTA', 'SEGURO', 'SERVICIO', 'OTRO']);
            $table->text('description')->nullable();
            $table->decimal('monthly_amount', 10, 2);

            // El check (due_day BETWEEN 1 AND 31) se maneja idealmente en el FormRequest, 
            // pero podemos usar un tinyInteger para optimizar el almacenamiento.
            $table->unsignedTinyInteger('due_day');

            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_expenses');
    }
};
