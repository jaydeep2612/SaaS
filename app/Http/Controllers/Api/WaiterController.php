<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class WaiterController extends Controller
{
    // 1. LOGIN: Returns the User & Restaurant ID
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->role !== 'waiter') {
            return response()->json(['message' => 'Unauthorized. Waiters only.'], 403);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('waiter-token')->plainTextToken // Requires Sanctum
        ]);
    }

    // 2. GET ORDERS: Only "Ready" orders for this restaurant
    public function getReadyOrders(Request $request)
    {
        // Simple security: You should use auth:sanctum middleware in production
        $restaurantId = $request->query('restaurant_id');

        $orders = Order::where('restaurant_id', $restaurantId)
            ->where('status', 'ready')
            ->with(['items.menuItem', 'table']) // Load items and table info
            ->orderBy('updated_at', 'asc') // Oldest ready orders first
            ->get();

        return response()->json($orders);
    }

    // 3. MARK SERVED: Change status from 'ready' to 'served'
    public function markServed($id)
    {
        $order = Order::find($id);

        if ($order && $order->status === 'ready') {
            $order->update(['status' => 'served']);
            return response()->json(['message' => 'Order served successfully']);
        }

        return response()->json(['message' => 'Order not found or not ready'], 404);
    }
}