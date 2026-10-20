<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contents')
            ->where('type', 'article')
            ->update([
                'expired_at' => null,
            ]);
    }

    public function down(): void
    {
        //
    }
};
