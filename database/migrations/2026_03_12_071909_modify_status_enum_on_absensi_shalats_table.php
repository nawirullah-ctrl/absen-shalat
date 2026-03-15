<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE absensi_shalats 
            MODIFY status ENUM('hadir', 'masbuk', 'izin', 'sakit', 'alpha') 
            NOT NULL DEFAULT 'hadir'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE absensi_shalats 
            MODIFY status ENUM('hadir', 'izin', 'sakit', 'alpha') 
            NOT NULL DEFAULT 'hadir'
        ");
    }
};