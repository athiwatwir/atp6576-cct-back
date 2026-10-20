<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference', 50)->nullable()->after('transaction_id');
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->string('failure_code', 50)->nullable()->after('expires_at');
            $table->string('failure_message')->nullable()->after('failure_code');

            $table->unique('reference', 'uq_payments_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('uq_payments_reference');
            $table->dropColumn(['reference', 'expires_at', 'failure_code', 'failure_message']);
        });
    }
};
