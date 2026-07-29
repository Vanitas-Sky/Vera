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
    Schema::create('sat_product_services', function (Blueprint $table) {
        $table->string('code', 8)->primary();
        $table->string('description', 255);
        $table->text('similar_words')->nullable(); // nullable para evitar errores si está vacío
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sat_product_services');
    }
};
