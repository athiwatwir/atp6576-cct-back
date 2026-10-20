<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained('assessments')->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'is_active'], 'idx_price_plans_course');
            $table->index(['curriculum_id', 'is_active'], 'idx_price_plans_curriculum');
            $table->index(['assessment_id', 'is_active'], 'idx_price_plans_assessment');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('price_plan_id')->nullable()->after('assessment_id')->constrained('price_plans')->nullOnDelete();
            $table->unsignedInteger('access_days')->nullable()->after('price_plan_id');
        });

        $now = now();

        foreach ([
            ['courses', 'course_id', fn ($query) => $query],
            ['curriculums', 'curriculum_id', fn ($query) => $query],
            ['assessments', 'assessment_id', fn ($query) => $query->where('type', 'primary_exam')],
        ] as [$table, $column, $scope]) {
            $hasSale = Schema::hasColumn($table, 'sale_price');

            $scope(DB::table($table)->whereNull('deleted_at'))
                ->orderBy('id')
                ->select(array_filter(['id', 'price', $hasSale ? 'sale_price' : null]))
                ->chunkById(200, function ($rows) use ($column, $hasSale, $now) {
                    DB::table('price_plans')->insert($rows->map(fn ($row) => [
                        $column => $row->id,
                        'name' => null,
                        'duration_days' => null,
                        'price' => $row->price ?? 0,
                        'sale_price' => $hasSale ? $row->sale_price : null,
                        'is_active' => true,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all());
                });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_plan_id');
            $table->dropColumn('access_days');
        });

        Schema::dropIfExists('price_plans');
    }
};
