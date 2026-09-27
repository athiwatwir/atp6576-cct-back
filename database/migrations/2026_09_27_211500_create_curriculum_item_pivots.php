<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_chapters', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'chapter_id']);
        });

        Schema::create('curriculum_assessments', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'assessment_id']);
        });

        Schema::create('curriculum_products', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_products');
        Schema::dropIfExists('curriculum_assessments');
        Schema::dropIfExists('curriculum_chapters');
    }
};
