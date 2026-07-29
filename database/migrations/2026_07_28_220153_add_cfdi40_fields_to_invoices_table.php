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
            $table->string('issuer_regimen', 10)->nullable()->after('issuer_name');
            $table->string('issuer_cp', 10)->nullable()->after('issuer_regimen');

            $table->string('receiver_regimen', 10)->nullable()->after('receiver_name');
            $table->string('receiver_cp', 10)->nullable()->after('receiver_regimen');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['issuer_regimen', 'issuer_cp', 'receiver_regimen', 'receiver_cp']);
        });
    }
};
