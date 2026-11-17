<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'fulfillment_type')) {
                $table->string('fulfillment_type', 20)->default('pickup')->after('status');
            }
            if (!Schema::hasColumn('orders', 'delivery_fee_minor')) {
                $table->integer('delivery_fee_minor')->default(0)->after('total_minor');
            }
            if (!Schema::hasColumn('orders', 'delivery_address')) {
                $table->string('delivery_address')->nullable()->after('pickup_scheduled_at');
            }
            if (!Schema::hasColumn('orders', 'delivery_recipient_phone')) {
                $table->string('delivery_recipient_phone', 30)->nullable()->after('delivery_address');
            }
            if (!Schema::hasColumn('orders', 'delivery_lat')) {
                $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_recipient_phone');
            }
            if (!Schema::hasColumn('orders', 'delivery_lng')) {
                $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
            }
            if (!Schema::hasColumn('orders', 'delivery_distance_km')) {
                $table->decimal('delivery_distance_km', 6, 2)->nullable()->after('delivery_lng');
            }
            if (!Schema::hasColumn('orders', 'courier_order_id')) {
                $table->string('courier_order_id')->nullable()->after('delivery_distance_km');
            }
            if (!Schema::hasColumn('orders', 'courier_status')) {
                $table->string('courier_status', 40)->nullable()->after('courier_order_id');
            }
            if (!Schema::hasColumn('orders', 'courier_driver_name')) {
                $table->string('courier_driver_name', 100)->nullable()->after('courier_status');
            }
            if (!Schema::hasColumn('orders', 'courier_driver_plate')) {
                $table->string('courier_driver_plate', 30)->nullable()->after('courier_driver_name');
            }
            if (!Schema::hasColumn('orders', 'courier_share_link')) {
                $table->text('courier_share_link')->nullable()->after('courier_driver_plate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fulfillment_type',
                'delivery_fee_minor',
                'delivery_address',
                'delivery_recipient_phone',
                'delivery_lat',
                'delivery_lng',
                'delivery_distance_km',
                'courier_order_id',
                'courier_status',
                'courier_driver_name',
                'courier_driver_plate',
                'courier_share_link',
            ]);
        });
    }
};