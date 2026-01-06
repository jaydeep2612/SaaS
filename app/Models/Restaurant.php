<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!


class Restaurant extends Model
{
   protected $guarded = [];

    public function users() { return $this->hasMany(User::class); }
    public function categories() { return $this->hasMany(Category::class); }
    public function tables() { return $this->hasMany(RestaurantTable::class); }
    public function orders() { return $this->hasMany(Order::class); }
    protected static function booted()
    {
        static::creating(function ($restaurant) {
            // Automatically set the current user ID when creating
            if (Auth::check()) {
                $restaurant->created_by = Auth::id();
            }
        });
    }
}
