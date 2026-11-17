<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_driver_phone', 30)->nullable()->after('courier_driver_plate');
            $table->integer('refunded_minor')->default(0)->after('total_minor');
            $table->boolean('delivery_fee_forfeited')->default(false)->after('refunded_minor');
            $table->boolean('stock_returned')->default(true)->after('delivery_fee_forfeited');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'courier_driver_phone',
                'refunded_minor',
                'delivery_fee_forfeited',
                'stock_returned',
            ]);
        });
    }
};