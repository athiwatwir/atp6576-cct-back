<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assessments')
            ->where('is_independent', true)
            ->where('type', 'exam')
            ->update(['type' => 'primary_exam']);
    }

    public function down(): void
    {
        DB::table('assessments')
            ->where('is_independent', true)
            ->where('type', 'primary_exam')
            ->update(['type' => 'exam']);
    }
};
