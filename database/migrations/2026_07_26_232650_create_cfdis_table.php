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
        Schema::create('cfdis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('rfc_issuer', 13);
            $table->string('name_issuer', 255);
            $table->string('rfc_receiver', 13);
            $table->string('name_receiver', 255);

            $table->enum('invoice_type', ['I', 'E', 'N']); // Ingreso, Egreso, Nómina
            $table->dateTime('issue_date');

            // Manejo de moneda: 12 enteros, 2 decimales
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0.00);
            $table->decimal('vat_retention', 12, 2)->default(0.00);
            $table->decimal('isr_retention', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2);

            // Llaves foráneas hacia catálogos SAT (Strings)
            $table->string('payment_method_code', 5)->nullable();
            $table->foreign('payment_method_code')->references('code')->on('sat_payment_methods');

            $table->string('payment_form_code', 5)->nullable();
            $table->foreign('payment_form_code')->references('code')->on('sat_payment_forms');

            $table->string('cfdi_use_code', 5)->nullable();
            $table->foreign('cfdi_use_code')->references('code')->on('sat_cfdi_uses');

            $table->enum('deductibility_status', ['VERDE', 'AMARILLO', 'ROJO'])->default('VERDE');
            $table->string('raw_xml_path', 500)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdis');
    }
};
