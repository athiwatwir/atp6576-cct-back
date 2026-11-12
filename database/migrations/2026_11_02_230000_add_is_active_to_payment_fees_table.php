<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_fees', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('payment_method');
        });

        // คงพฤติกรรมเดิม: QR และบัตรตาม PAYMENT_*_ENABLED (ค่าเริ่มต้นเปิด) โอนเงินเปิดเสมอ e-Wallet ปิด
        $defaults = [
            'thai_qr' => (bool) env('PAYMENT_THAI_QR_ENABLED', true),
            'credit_card' => (bool) env('PAYMENT_CARD_ENABLED', true),
            'bank_transfer' => true,
        ];

        foreach ($defaults as $method => $active) {
            $exists = DB::table('payment_fees')->where('payment_method', $method)->exists();

            if ($exists) {
                DB::table('payment_fees')->where('payment_method', $method)->update(['is_active' => $active]);
            } else {
                DB::table('payment_fees')->insert([
                    'payment_method' => $method,
                    'is_active' => $active,
                    'is_enabled' => false,
                    'percent' => 0,
                    'fixed_amount' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payment_fees', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
