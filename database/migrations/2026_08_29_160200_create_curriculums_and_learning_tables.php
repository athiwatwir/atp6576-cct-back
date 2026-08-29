<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 280)->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('storage_provider', 50)->nullable();
            $table->string('storage_key', 1000)->nullable();
            $table->string('hls_path', 1000)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_free')->default(false);
            $table->string('status', 30)->default('draft')->index('idx_videos_status');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chapter_videos', function (Blueprint $table) {
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->primary(['chapter_id', 'video_id']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path', 1000);
            $table->string('file_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_free')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chapter_documents', function (Blueprint $table) {
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['chapter_id', 'document_id']);
        });

        Schema::create('curriculums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug', 280)->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('status', 30)->default('draft')->index('idx_curriculum_status');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'course_id']);
        });

        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'subject_id']);
        });

        Schema::create('curriculum_videos', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'video_id']);
        });

        Schema::create('curriculum_documents', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['curriculum_id', 'document_id']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('source', 50)->default('purchase');
            $table->string('status', 30)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index('idx_enrollment_expires');
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_enrollment_user_status');
        });

        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->unsignedInteger('position_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'video_id'], 'uq_video_progress_user_video');
        });

        Schema::create('learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->cascadeOnDelete();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->unsignedInteger('completed_items')->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id'], 'uq_learning_progress_user_course');
            $table->index(['user_id', 'curriculum_id'], 'idx_learning_progress_curriculum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_progress');
        Schema::dropIfExists('video_progress');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('curriculum_documents');
        Schema::dropIfExists('curriculum_videos');
        Schema::dropIfExists('curriculum_subjects');
        Schema::dropIfExists('curriculum_courses');
        Schema::dropIfExists('curriculums');
        Schema::dropIfExists('chapter_documents');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('chapter_videos');
        Schema::dropIfExists('videos');
    }
};
