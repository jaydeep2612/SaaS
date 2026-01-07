<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use App\Models\RestaurantTable;
use App\Models\Restaurant;
use App\Models\OrderItem;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id', 'table_id', 'total_amount', 'status', 'created_by'
    ];

    // 👇 Auto-stamp Restaurant ID and Creator ID
    protected static function booted()
    {
        static::creating(function ($order) {
            if (Auth::check()) {
                if (!$order->restaurant_id) {
                    $order->restaurant_id = Auth::user()->restaurant_id;
                }
                if (!$order->created_by) {
                    $order->created_by = Auth::id();
                }
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function table(): BelongsTo
    {
        // Note: Make sure your table model is named 'RestaurantTable' or 'Table'
        // If you named it 'Table', use Table::class. If 'RestaurantTable', use RestaurantTable::class
        return $this->belongsTo(RestaurantTable::class, 'table_id'); 
    }
    
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
