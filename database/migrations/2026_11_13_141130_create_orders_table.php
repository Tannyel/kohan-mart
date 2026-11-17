<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->unique(); // Prevents duplicate checkouts
            $table->unsignedBigInteger('total_minor'); // Total in centavos
            $table->enum('status', [
                'pending_confirmation',
                'confirmed',
                'ready_for_pickup',
                'completed',
                'cancelled'
            ])->default('pending_confirmation');
            $table->boolean('is_paid')->default(false); // Recorded upon in-store pickup
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};