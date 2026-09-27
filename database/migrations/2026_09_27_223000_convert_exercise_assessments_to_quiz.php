<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assessments')->where('type', 'exercise')->update(['type' => 'quiz']);
    }

    public function down(): void
    {
        // แยก type เดิมกลับไม่ได้ เพราะไม่มีข้อมูลว่าแถวไหนเคยเป็น exercise
    }
};
