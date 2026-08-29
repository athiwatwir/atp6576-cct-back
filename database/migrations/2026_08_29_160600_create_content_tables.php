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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('title');
            $table->string('slug', 280)->nullable()->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('image', 500)->nullable();
            $table->string('link_url', 1000)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status'], 'idx_contents_type_status');
            $table->index(['published_at', 'expired_at'], 'idx_contents_publish');
        });

        Schema::create('content_courses', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->primary(['content_id', 'course_id']);
        });

        Schema::create('content_curriculums', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained('curriculums')->cascadeOnDelete();
            $table->primary(['content_id', 'curriculum_id']);
        });

        Schema::create('content_promotions', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->primary(['content_id', 'promotion_id']);
        });

        Schema::create('content_coupons', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->primary(['content_id', 'coupon_id']);
        });

        Schema::create('content_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 30);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['content_id', 'event_type'], 'idx_content_stats_content_event');
            $table->index('created_at', 'idx_content_stats_created');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
        });

        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedInteger('rank_position')->nullable();
            $table->string('period_type', 30)->default('all_time');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();

            $table->index(['assessment_id', 'period_type', 'period_start', 'period_end'], 'idx_rankings_assessment_period');
            $table->index(['assessment_id', 'score'], 'idx_rankings_score');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index('idx_audit_action');
            $table->string('entity_type', 150)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
            $table->index('created_at', 'idx_audit_created');
        });

        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at'], 'idx_payment_links_status_expiry');
        });

        Schema::create('payment_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('file_path', 1000);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('trial_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable()->index('idx_trial_expiry');
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_trial_user_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_access');
        Schema::dropIfExists('payment_slips');
        Schema::dropIfExists('payment_links');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('rankings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('content_stats');
        Schema::dropIfExists('content_coupons');
        Schema::dropIfExists('content_promotions');
        Schema::dropIfExists('content_curriculums');
        Schema::dropIfExists('content_courses');
        Schema::dropIfExists('contents');
    }
};
