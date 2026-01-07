<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class RestaurantTable extends Model
{
    protected $fillable = [
        'restaurant_id',
        'table_number',
        'capacity',
        'status',
        'created_by',

    ];

    // 👇 Auto-assign Restaurant ID when creating
    protected static function booted()
    {
        static::creating(function ($table) {
            if (Auth::check() && !$table->restaurant_id) {
                $table->restaurant_id = Auth::user()->restaurant_id;
            }
            if (Auth::check() && !$table->created_by) {
            $table->created_by = Auth::id();
        }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
