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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 280)->unique();
            $table->text('description')->nullable();
            $table->string('type', 30)->default('quiz');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('passing_score', 8, 2)->nullable();
            $table->unsignedInteger('max_attempts')->nullable();
            $table->boolean('is_independent')->default(false);
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status'], 'idx_assessment_type_status');
        });

        Schema::create('assessment_courses', function (Blueprint $table) {
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained('chapters')->nullOnDelete();
            $table->primary(['assessment_id', 'course_id']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->nullable()->constrained('assessments')->nullOnDelete();
            $table->longText('question_text');
            $table->string('question_type', 30)->default('single_choice');
            $table->text('explanation')->nullable();
            $table->decimal('points', 8, 2)->default(1);
            $table->integer('sort_order')->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        Schema::create('question_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('choice_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('status', 30)->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 10, 2)->default(0);
            $table->decimal('max_score', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->boolean('passed')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'assessment_id'], 'idx_attempt_user_assessment');
            $table->index(['assessment_id', 'score'], 'idx_attempt_score');
        });

        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('selected_choice_id')->nullable()->constrained('question_choices')->nullOnDelete();
            $table->longText('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id'], 'uq_attempt_question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('question_choices');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('assessment_courses');
        Schema::dropIfExists('assessments');
    }
};
