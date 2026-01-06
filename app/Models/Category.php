<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth; // 👈 Don't forget this import!
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 👈 Import this

class Category extends Model
{
    protected $fillable = ['name', 'restaurant_id'];
    protected static function booted()
    {
        static::creating(function ($user) {
            // Automatically set the current user ID when creating
            if (Auth::check()) {
                $user->created_by = Auth::id();
            }
        });
    }
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
