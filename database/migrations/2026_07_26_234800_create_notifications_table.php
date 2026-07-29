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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->enum('alert_type', ['VENCIMIENTO', 'DISCREPANCIA', 'ERROR_FORMA']);
            $table->enum('priority', ['BAJA', 'MEDIA', 'ALTA'])->default('MEDIA');

            $table->string('title', 150);
            $table->text('message');
            $table->boolean('is_read')->default(false);

            // Usamos useCurrent() para que la BD inserte la fecha automáticamente
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
