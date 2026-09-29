<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('discount_type', 30)->default('percentage')->after('description');
            $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
            $table->decimal('max_discount_amount', 12, 2)->nullable()->after('discount_value');
            $table->decimal('min_purchase_amount', 12, 2)->nullable()->after('max_discount_amount');
        });

        Schema::create('coupon_courses', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->primary(['coupon_id', 'course_id']);
        });

        Schema::create('coupon_curriculums', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->primary(['coupon_id', 'curriculum_id']);
        });

        Schema::create('coupon_assessments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->primary(['coupon_id', 'assessment_id']);
        });

        Schema::create('coupon_products', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        $targets = [
            'course' => ['promotion_courses', 'coupon_courses', 'course_id'],
            'curriculum' => ['promotion_curriculums', 'coupon_curriculums', 'curriculum_id'],
            'assessment' => ['promotion_assessments', 'coupon_assessments', 'assessment_id'],
            'product' => ['promotion_products', 'coupon_products', 'product_id'],
        ];

        foreach (DB::table('coupons')->whereNotNull('promotion_id')->get() as $coupon) {
            $promotion = DB::table('promotions')->where('id', $coupon->promotion_id)->first();

            if (! $promotion) {
                continue;
            }

            DB::table('coupons')->where('id', $coupon->id)->update([
                'discount_type' => $promotion->discount_type,
                'discount_value' => $promotion->discount_value,
                'max_discount_amount' => $promotion->max_discount_amount,
                'min_purchase_amount' => $promotion->min_purchase_amount,
                'start_at' => $coupon->start_at ?? $promotion->start_at,
                'end_at' => $coupon->end_at ?? $promotion->end_at,
            ]);

            foreach ($targets as [$source, $destination, $column]) {
                $ids = DB::table($source)->where('promotion_id', $promotion->id)->pluck($column);

                foreach ($ids as $id) {
                    DB::table($destination)->insert([
                        'coupon_id' => $coupon->id,
                        $column => $id,
                    ]);
                }
            }
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('id')->constrained('promotions')->nullOnDelete();
            $table->dropColumn([
                'discount_type',
                'discount_value',
                'max_discount_amount',
                'min_purchase_amount',
            ]);
        });

        Schema::dropIfExists('coupon_products');
        Schema::dropIfExists('coupon_assessments');
        Schema::dropIfExists('coupon_curriculums');
        Schema::dropIfExists('coupon_courses');
    }
};
