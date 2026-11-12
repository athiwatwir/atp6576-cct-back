<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * รวมเงื่อนไขและของแถมของโปรโมชันเข้ากับคูปอง (เมนูโปรโมชันเลิกใช้)
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> ตาราง => [คอลัมน์, ตารางปลายทาง] */
    private const PIVOTS = [
        'coupon_required_courses' => ['course_id', 'courses'],
        'coupon_required_products' => ['product_id', 'products'],
        'coupon_required_videos' => ['video_id', 'videos'],
        'coupon_gift_courses' => ['course_id', 'courses'],
        'coupon_gift_assessments' => ['assessment_id', 'assessments'],
        'coupon_gift_products' => ['product_id', 'products'],
    ];

    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('student_group', 20)->default('all')->after('per_user_limit');
            $table->unsignedInteger('required_video_seconds')->nullable()->after('student_group');
        });

        foreach (self::PIVOTS as $name => [$column, $target]) {
            Schema::create($name, function (Blueprint $table) use ($column, $target) {
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId($column)->constrained($target)->cascadeOnDelete();
                $table->primary(['coupon_id', $column]);
            });
        }

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->timestamp('gifts_granted_at')->nullable()->after('used_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('gift_for_order_id')->nullable()->after('coupon_id')->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_for_order_id');
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropColumn('gifts_granted_at');
        });

        foreach (array_keys(self::PIVOTS) as $name) {
            Schema::dropIfExists($name);
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['student_group', 'required_video_seconds']);
        });
    }
};
