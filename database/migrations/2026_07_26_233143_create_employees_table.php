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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('rfc', 13);
            $table->string('curp', 18);
            $table->string('full_name', 200);
            $table->string('nss', 11);
            $table->decimal('base_salary', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // Maneja created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
