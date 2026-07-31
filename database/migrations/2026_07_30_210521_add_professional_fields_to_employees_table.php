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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('email')->nullable()->after('full_name');
            $table->string('position')->nullable()->after('email');
            $table->string('clabe', 18)->nullable()->after('nss');
            $table->date('hire_date')->nullable()->after('clabe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            //
        });
    }
};
