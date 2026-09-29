<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_required_courses', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->primary(['promotion_id', 'course_id']);
        });

        Schema::create('promotion_required_products', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['promotion_id', 'product_id']);
        });

        Schema::create('promotion_required_videos', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->primary(['promotion_id', 'video_id']);
        });

        Schema::create('promotion_gift_courses', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->primary(['promotion_id', 'course_id']);
        });

        Schema::create('promotion_gift_assessments', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->primary(['promotion_id', 'assessment_id']);
        });

        Schema::create('promotion_gift_products', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['promotion_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_gift_products');
        Schema::dropIfExists('promotion_gift_assessments');
        Schema::dropIfExists('promotion_gift_courses');
        Schema::dropIfExists('promotion_required_videos');
        Schema::dropIfExists('promotion_required_products');
        Schema::dropIfExists('promotion_required_courses');
    }
};
