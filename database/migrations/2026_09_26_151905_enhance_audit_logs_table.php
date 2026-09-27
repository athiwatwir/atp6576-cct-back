<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('action');
            $table->json('properties')->nullable()->after('new_values');
            $table->string('batch_uuid', 36)->nullable()->after('properties')->index('idx_audit_batch');
            $table->string('request_method', 10)->nullable()->after('user_agent');
            $table->string('request_url', 1000)->nullable()->after('request_method');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_batch');
            $table->dropColumn([
                'description',
                'properties',
                'batch_uuid',
                'request_method',
                'request_url',
            ]);
        });
    }
};
