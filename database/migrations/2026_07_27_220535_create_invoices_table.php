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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Datos Fiscales Clave
            $table->string('uuid')->unique(); // Folio Fiscal del SAT (36 caracteres)
            $table->string('issuer_rfc'); // RFC del Emisor (Quien emite la factura)
            $table->string('receiver_rfc'); // RFC del Receptor (Quien la recibe)
            $table->string('type', 1); // Tipo de comprobante: I (Ingreso), E (Egreso), P (Pago), etc.
            
            // Fechas
            $table->dateTime('issue_date'); // Fecha de emisión
            
            // Totales (12 enteros, 2 decimales para soportar montos grandes)
            $table->decimal('subtotal', 12, 2);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
