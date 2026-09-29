<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->string('placement', 30)->nullable()->after('type');
            $table->unsignedInteger('sort_order')->default(0)->after('status');
            $table->index(['type', 'placement', 'sort_order'], 'idx_contents_placement_order');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropIndex('idx_contents_placement_order');
            $table->dropColumn(['placement', 'sort_order']);
        });
    }
};
