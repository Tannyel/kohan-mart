<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'birthday')) {
                $table->date('birthday')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'points_balance')) {
                $table->decimal('points_balance', 10, 2)->default(0)->after('birthday');
            }
            if (!Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code', 10)->nullable()->after('points_balance');
            }
            if (!Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'subtotal_minor')) {
                $table->unsignedBigInteger('subtotal_minor')->default(0)->after('total_minor');
            }
            if (!Schema::hasColumn('orders', 'points_used')) {
                $table->decimal('points_used', 10, 2)->default(0)->after('subtotal_minor');
            }
            if (!Schema::hasColumn('orders', 'points_discount_minor')) {
                $table->unsignedBigInteger('points_discount_minor')->default(0)->after('points_used');
            }
            if (!Schema::hasColumn('orders', 'points_earned')) {
                $table->decimal('points_earned', 10, 2)->default(0)->after('points_discount_minor');
            }
            if (!Schema::hasColumn('orders', 'points_awarded')) {
                $table->boolean('points_awarded')->default(false)->after('points_earned');
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
        });

        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 32); // welcome_bonus, earned, redeemed, refunded
                $table->decimal('points', 10, 2); // positive for credit, negative for debit
                $table->string('description');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_minor',
                'points_used',
                'points_discount_minor',
                'points_earned',
                'points_awarded',
                'paid_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'birthday',
                'points_balance',
                'otp_code',
                'otp_expires_at',
            ]);
        });
    }
};