<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuItem;

class OrderItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['order_id', 'menu_item_id', 'quantity', 'price'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
