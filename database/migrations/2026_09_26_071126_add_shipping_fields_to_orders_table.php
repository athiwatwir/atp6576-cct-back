<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('requires_shipping')->default(true)->after('notes');
            $table->string('shipping_name', 150)->nullable()->after('requires_shipping');
            $table->string('shipping_phone', 30)->nullable()->after('shipping_name');
            $table->string('shipping_address_line1', 255)->nullable()->after('shipping_phone');
            $table->string('shipping_address_line2', 255)->nullable()->after('shipping_address_line1');
            $table->string('shipping_subdistrict', 100)->nullable()->after('shipping_address_line2');
            $table->string('shipping_district', 100)->nullable()->after('shipping_subdistrict');
            $table->string('shipping_province', 100)->nullable()->after('shipping_district');
            $table->string('shipping_postal_code', 20)->nullable()->after('shipping_province');
            $table->string('shipping_status', 30)->default('pending')->after('shipping_postal_code');
            $table->string('shipping_carrier', 100)->nullable()->after('shipping_status');
            $table->string('tracking_number', 100)->nullable()->after('shipping_carrier');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');

            $table->index(['shipping_status', 'tracking_number'], 'idx_orders_shipping_tracking');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_shipping_tracking');
            $table->dropColumn([
                'requires_shipping',
                'shipping_name',
                'shipping_phone',
                'shipping_address_line1',
                'shipping_address_line2',
                'shipping_subdistrict',
                'shipping_district',
                'shipping_province',
                'shipping_postal_code',
                'shipping_status',
                'shipping_carrier',
                'tracking_number',
                'shipped_at',
                'delivered_at',
            ]);
        });
    }
};
