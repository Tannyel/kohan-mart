<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'idempotency_key',
        'order_number',
        'status',
        'cancelled_by',
        'fulfillment_type',
        'payment_method',
        'is_paid',
        'paid_at',
        'paid_at_micro',
        'pickup_scheduled_at',
        'delivery_address',
        'delivery_recipient_phone',
        'delivery_lat',
        'delivery_lng',
        'delivery_distance_km',
        'courier_order_id',
        'courier_status',
        'courier_driver_name',
        'courier_driver_plate',
        'courier_driver_phone',
        'courier_share_link',
        'subtotal_minor',
        'points_used',
        'points_discount_minor',
        'delivery_fee_minor',
        'total_minor',
        'refunded_minor',
        'delivery_fee_forfeited',
        'stock_returned',
        'points_earned',
        'points_awarded',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'points_awarded' => 'boolean',
        'delivery_fee_forfeited' => 'boolean',
        'stock_returned' => 'boolean',
        'paid_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'points_used' => 'decimal:2',
        'points_earned' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->idempotency_key)) {
                $order->idempotency_key = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isDelivery(): bool
    {
        return $this->fulfillment_type === 'delivery';
    }

    /**
     * Customers may self-cancel only before a Lalamove rider is assigned.
     */
    public function canCustomerCancel(): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed'], true)) {
            return false;
        }

        if ($this->isDelivery()) {
            return ($this->courier_status ?? 'order_placed') === 'order_placed';
        }

        return true;
    }

    /**
     * True once the Lalamove rider has physically collected the bag from Project 8.
     */
    public function hasLeftStore(): bool
    {
        return $this->isDelivery()
            && in_array($this->courier_status, ['picked_up', 'on_the_way'], true);
    }

    /**
     * True when an in-transit delivery was cancelled and stock awaits physical return to the shelf.
     */
    public function isAwaitingStockReturn(): bool
    {
        return $this->status === 'cancelled'
            && $this->isDelivery()
            && !$this->stock_returned;
    }
}