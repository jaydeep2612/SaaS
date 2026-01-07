<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class MenuItem extends Model
{
    protected static function booted()
{
    static::creating(function ($menuItem) {
        // 1. Auto-fill Restaurant ID
        if (Auth::check() && !$menuItem->restaurant_id) {
            $menuItem->restaurant_id = Auth::user()->restaurant_id;
        }

        // 2. Auto-fill Created By (The Fix)
        if (Auth::check() && !$menuItem->created_by) {
            $menuItem->created_by = Auth::id();
        }
    });
}

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'is_available',
    ];
    

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}