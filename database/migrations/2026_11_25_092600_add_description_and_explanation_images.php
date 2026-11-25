<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('description_image', 500)->nullable()->after('description');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('explanation_image', 500)->nullable()->after('explanation');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('description_image');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('explanation_image');
        });
    }
};
