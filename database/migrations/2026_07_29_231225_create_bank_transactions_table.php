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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();

            // Llave foránea para aislar los datos por empresa
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Datos mapeados del banco
            $table->date('transaction_date');
            $table->text('description');

            // DECIMAL(12,2) permite manejar hasta miles de millones con dos decimales exactos
            $table->decimal('withdrawal', 12, 2)->default(0); // Cargos / Retiros
            $table->decimal('deposit', 12, 2)->default(0);    // Abonos / Entradas

            $table->timestamps();
        });
    }
};
