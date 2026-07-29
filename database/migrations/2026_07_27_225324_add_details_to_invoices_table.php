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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();
            $table->string('moneda')->default('MXN');
            $table->string('metodo_pago')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('uso_cfdi')->nullable();
            $table->string('issuer_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->json('items')->nullable(); // Guardará los conceptos de la factura
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            //
        });
    }
};
