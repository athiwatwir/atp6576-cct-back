<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('school_name', 255)->nullable()->after('phone');
            $table->string('address_line1', 255)->nullable()->after('school_name');
            $table->string('subdistrict', 100)->nullable()->after('address_line1');
            $table->string('district', 100)->nullable()->after('subdistrict');
            $table->string('province', 100)->nullable()->after('district');
            $table->string('postal_code', 10)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'school_name',
                'address_line1',
                'subdistrict',
                'district',
                'province',
                'postal_code',
            ]);
        });
    }
};
