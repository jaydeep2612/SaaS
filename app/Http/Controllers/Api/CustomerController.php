<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    // 1. GET MENU (Grouped by Category)
    public function getMenu($restaurantId)
    {
        $categories = Category::where('restaurant_id', $restaurantId)
            ->with(['menuItems' => function($query) {
                $query->where('is_available', true);
            }])
            ->get();

        return response()->json($categories);
    }

    // 2. PLACE ORDER
    public function placeOrder(Request $request)
    {
        // Validation
        $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id' => 'required|exists:restaurant_tables,id',
            'customer_name' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // A. Calculate Total
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $menuItem = MenuItem::find($item['id']);
                $totalAmount += $menuItem->price * $item['quantity'];
            }

            // B. Create Order
            $order = Order::create([
                'restaurant_id' => $request->restaurant_id,
                'table_id' => $request->table_id,
                'customer_name' => $request->customer_name, // 👈 Storing name here
                'total_amount' => $totalAmount,
                'status' => 'placed', // Ready for Kitchen
                'payment_status' => 'unpaid'
            ]);

            // C. Create Order Items
            foreach ($request->items as $item) {
                $menuItem = MenuItem::find($item['id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $item['quantity'],
                    'price' => $menuItem->price
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Order placed successfully!', 'order_id' => $order->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order failed', 'error' => $e->getMessage()], 500);
        }
    }
}