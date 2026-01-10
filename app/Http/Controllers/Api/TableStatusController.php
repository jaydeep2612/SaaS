<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class TableStatusController extends Controller
{
    // 1. Check Table Status (When QR is scanned)
    public function checkStatus($id)
    {
        $table = RestaurantTable::findOrFail($id);
        
        return response()->json([
            'status' => $table->status,
            'customer_name' => $table->current_customer_name,
            'restaurant_id' => $table->restaurant_id,
            'table_number' => $table->table_number
        ]);
    }

    // 2. Occupy Table (When Customer enters name)
    public function occupy(Request $request, $id)
    {
        $request->validate([
            'customer_name' => 'required|string|max:50',
        ]);

        $table = RestaurantTable::findOrFail($id);

        // Security: Prevent overriding an active session unless it's available
        if ($table->status === 'occupied') {
             // Allow re-entry if needed, or block it. 
             // For now, we block it to prevent conflicts.
             return response()->json(['message' => 'Table is already occupied'], 409);
        }

        $table->update([
            'status' => 'occupied',
            'current_customer_name' => $request->customer_name,
        ]);

        return response()->json(['message' => 'Welcome! Table is now occupied.']);
    }

    // 3. Free Table (Called by Waiter or Payment success)
    public function free($id)
    {
        $table = RestaurantTable::findOrFail($id);
        
        $table->update([
            'status' => 'available',
            'current_customer_name' => null,
        ]);

        return response()->json(['message' => 'Table is now free']);
    }
}