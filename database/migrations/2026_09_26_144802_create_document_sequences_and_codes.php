<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 30);
            $table->string('period_key', 20)->default('');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['doc_type', 'period_key'], 'uq_document_sequences_type_period');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->unique()->after('id');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->unique()->after('id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::dropIfExists('document_sequences');
    }
};
