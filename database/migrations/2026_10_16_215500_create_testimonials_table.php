<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('content');
            $table->string('image', 500)->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'sort_order'], 'idx_testimonials_status_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
