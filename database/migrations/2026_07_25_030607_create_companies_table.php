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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('rfc', 13)->unique();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('postal_code', 10);
            $table->string('tax_regime_code', 10); // Referencia logica a sat_tax_regimes (Modulo 2)
            $table->string('pac_api_key_sandbox')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
