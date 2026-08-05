<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- IMPORTANTE

return new class extends Migration
{
    public function up(): void
    {
        // 1. Matamos el ENUM y lo convertimos a VARCHAR(30) usando SQL nativo. 
        DB::statement("ALTER TABLE employee_deductions MODIFY amount_type VARCHAR(30) NOT NULL");

        // 2. Actualizamos el registro "huérfano" para que coincida con la nueva regla
        DB::table('employee_deductions')
            ->where('amount_type', 'percentage')
            ->update(['amount_type' => 'percentage_net']);
    }

    public function down(): void
    {
        DB::table('employee_deductions')
            ->where('amount_type', 'percentage_net')
            ->update(['amount_type' => 'percentage']);
            
        DB::statement("ALTER TABLE employee_deductions MODIFY amount_type ENUM('fixed', 'percentage', 'vsm') NOT NULL");
    }
};
