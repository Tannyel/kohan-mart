<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->string('cancellation_reason', 120)->nullable()->after('cancelled_by');
            }
            if (!Schema::hasColumn('orders', 'cancellation_explanation')) {
                $table->text('cancellation_explanation')->nullable()->after('cancellation_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(['cancellation_reason', 'cancellation_explanation'], fn ($col) => Schema::hasColumn('orders', $col));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};