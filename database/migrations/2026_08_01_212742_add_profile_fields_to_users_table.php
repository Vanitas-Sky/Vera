<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('job_title', 100)->nullable()->after('phone');
            $table->string('timezone', 50)->default('America/Mexico_City')->after('job_title');
            $table->string('profile_photo_path', 2048)->nullable()->after('timezone'); // <-- La ruta de la foto
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'job_title', 'timezone', 'profile_photo_path']);
        });
    }
};
