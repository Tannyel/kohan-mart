<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'category',
        'description',
        'price_minor',
        'stock_quantity',
        'image_url',
        'is_active',
    ];

    public function reservations()
    {
        return $this->hasMany(StockReservation::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) $this->stock_quantity);
    }
}