<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kind', 20)->default('book')->after('order_no');
            $table->index('kind');
        });

        $learningOrderIds = DB::table('order_items')
            ->where(function ($query) {
                $query->whereNotNull('course_id')
                    ->orWhereNotNull('curriculum_id')
                    ->orWhereNotNull('assessment_id');
            })
            ->distinct()
            ->pluck('order_id');

        $bookOrderIds = DB::table('order_items')
            ->whereNotNull('product_id')
            ->distinct()
            ->pluck('order_id');

        $enrollmentIds = $learningOrderIds->diff($bookOrderIds)->values();

        if ($enrollmentIds->isNotEmpty()) {
            DB::table('orders')->whereIn('id', $enrollmentIds)->update(['kind' => 'enrollment']);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
