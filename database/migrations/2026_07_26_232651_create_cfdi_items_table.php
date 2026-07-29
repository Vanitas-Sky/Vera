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
        Schema::create('cfdi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfdi_id')->constrained('cfdis')->onDelete('cascade');

            // Llave foránea SAT (String)
            $table->string('sat_product_service_code', 8);
            $table->foreign('sat_product_service_code')->references('code')->on('sat_product_services');

            $table->integer('item_number');
            $table->text('original_description');
            $table->string('nlp_interpreted_category', 100)->nullable();

            // Cantidades pueden tener hasta 4 decimales en facturación
            $table->decimal('quantity', 10, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0.00);

            $table->enum('deductibility_status', ['VERDE', 'AMARILLO', 'ROJO'])->default('VERDE');

            // No agregamos timestamps porque el detalle de la factura es inmutable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdi_items');
    }
};
