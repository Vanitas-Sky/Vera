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
        Schema::table('cfdis', function (Blueprint $table) {
            $table->index(['company_id', 'deductibility_status'], 'idx_cfdis_deductibility');
        });

        Schema::table('cfdi_items', function (Blueprint $table) {
            $table->index('sat_product_service_code', 'idx_cfdi_items_prod_service');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['company_id', 'is_read'], 'idx_notifications_unread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdis', function (Blueprint $table) {
            $table->dropIndex('idx_cfdis_deductibility');
        });

        Schema::table('cfdi_items', function (Blueprint $table) {
            $table->dropIndex('idx_cfdi_items_prod_service');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_unread');
        });
    }
};
