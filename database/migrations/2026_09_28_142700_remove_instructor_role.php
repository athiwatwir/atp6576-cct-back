<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('name', 'instructor')->delete();
    }

    public function down(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'instructor'],
            [
                'display_name' => 'Instructor',
                'description' => 'Instructor access to course content',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
