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
        Schema::create('simulated_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->string('test_uuid', 36);
            $table->string('receiver_rfc', 13);
            $table->string('receiver_name', 255);
            $table->string('receiver_postal_code', 10);
            $table->string('receiver_tax_regime_code', 10);

            // Llaves foráneas hacia catálogos SAT (Strings)
            $table->string('cfdi_use_code', 5)->nullable();
            $table->foreign('cfdi_use_code')->references('code')->on('sat_cfdi_uses');

            $table->string('payment_method_code', 5)->nullable();
            $table->foreign('payment_method_code')->references('code')->on('sat_payment_methods');

            $table->string('payment_form_code', 5)->nullable();
            $table->foreign('payment_form_code')->references('code')->on('sat_payment_forms');

            $table->decimal('total', 12, 2);

            $table->string('pdf_sandbox_path', 500)->nullable();
            $table->string('xml_sandbox_path', 500)->nullable();

            // Desactivamos updated_at, solo guardamos cuándo se emitió la factura simulada
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulated_invoices');
    }
};
