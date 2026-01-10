'use client';

import { useState, useEffect, use } from 'react';
import axios from 'axios';
import { useRouter } from 'next/navigation';

export default function OrderPage({ params }) {
  // 1. Unwrap params (Next.js 15 requirement)
  const { restaurantId, tableId } = use(params);

  const router = useRouter();
  
  // State Management
  const [categories, setCategories] = useState([]);
  const [activeCategory, setActiveCategory] = useState('all');
  const [cart, setCart] = useState({}); // { itemId: quantity }
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [customerName, setCustomerName] = useState('');

  // 2. Load Data & Validate Session
  useEffect(() => {
    // A. Security Check: Ensure user checked in
    const storedName = localStorage.getItem('customer_name');
    const storedTable = localStorage.getItem('table_id');

    // If session is invalid or for wrong table, go back to check-in
    if (!storedName || storedTable !== tableId) {
      router.push(`/menu/${restaurantId}/${tableId}`);
      return;
    }
    setCustomerName(storedName);

    // B. Fetch Menu from API
    const fetchMenu = async () => {
      try {
        const res = await axios.get(`${process.env.NEXT_PUBLIC_API_URL}/customer/menu/${restaurantId}`);
        
        // ✅ FIX: API returns an array directly, so we use res.data directly
        setCategories(Array.isArray(res.data) ? res.data : []);
      } catch (error) {
        console.error("Failed to load menu", error);
      } finally {
        setLoading(false);
      }
    };

    if (restaurantId) fetchMenu();
  }, [restaurantId, tableId, router]);

  // 3. Cart Functions
  const addToCart = (item) => {
    setCart(prev => ({
      ...prev,
      [item.id]: (prev[item.id] || 0) + 1
    }));
  };

  const removeFromCart = (itemId) => {
    setCart(prev => {
      const newCart = { ...prev };
      if (newCart[itemId] > 1) {
        newCart[itemId] -= 1;
      } else {
        delete newCart[itemId];
      }
      return newCart;
    });
  };

  const getItemQuantity = (itemId) => cart[itemId] || 0;

  // Calculate Total Price
  const getTotalPrice = () => {
    let total = 0;
    categories.forEach(cat => {
      // ✅ FIX: Use 'menu_items' instead of 'items'
      (cat.menu_items || []).forEach(item => {
        if (cart[item.id]) {
          total += Number(item.price) * cart[item.id];
        }
      });
    });
    return total;
  };

  // 4. Place Order
  // 4. Place Order
  const placeOrder = async () => {
    setSubmitting(true);
    try {
      // ✅ FIX: Changed 'menu_item_id' to 'id' to match Backend Validation
      const orderItems = Object.keys(cart).map(itemId => ({
        id: itemId,           // 👈 The backend wants "id", not "menu_item_id"
        quantity: cart[itemId]
      }));

      // Debugging: See exactly what we are sending
      console.log("Sending Order Payload:", {
        restaurant_id: restaurantId,
        table_id: tableId,
        customer_name: customerName,
        items: orderItems,
        total_amount: getTotalPrice()
      });

      await axios.post(`${process.env.NEXT_PUBLIC_API_URL}/customer/order`, {
        restaurant_id: restaurantId,
        table_id: tableId,
        customer_name: customerName,
        items: orderItems, // Now contains [{ id: 7, quantity: 1 }, ...]
        total_amount: getTotalPrice()
      });

      alert("Order Placed Successfully! 🍳");
      setCart({}); 
      
    } catch (error) {
      console.error("Order Failed:", error.response?.data || error.message);
      alert(`Failed: ${error.response?.data?.message || "Check console for details"}`);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <div className="p-10 text-center animate-pulse">Loading Menu... 🥗</div>;

  return (
    <div className="min-h-screen bg-gray-50 pb-24"> 
      
      {/* Header */}
      <div className="bg-white p-4 shadow-sm sticky top-0 z-10">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-xl font-bold text-gray-800">Menu</h1>
            <p className="text-xs text-green-600 font-medium">👋 Hi, {customerName}</p>
          </div>
          <div className="text-right">
            <span className="text-sm text-gray-500">Table</span>
            <p className="text-xl font-bold text-gray-800">{tableId}</p>
          </div>
        </div>

        {/* Categories (Horizontal Scroll) */}
        <div className="flex gap-2 mt-4 overflow-x-auto pb-2 no-scrollbar">
          <button 
            onClick={() => setActiveCategory('all')}
            className={`px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${
              activeCategory === 'all' ? 'bg-black text-white' : 'bg-gray-100 text-gray-600'
            }`}
          >
            All Items
          </button>
          {categories.map(cat => (
            <button 
              key={cat.id}
              onClick={() => setActiveCategory(cat.id)}
              className={`px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${
                activeCategory === cat.id ? 'bg-black text-white' : 'bg-gray-100 text-gray-600'
              }`}
            >
              {cat.name}
            </button>
          ))}
        </div>
      </div>

      {/* Menu List */}
      <div className="p-4 space-y-6">
        {categories.map(category => {
          // Filter by active category
          if (activeCategory !== 'all' && activeCategory !== category.id) return null;
          
          return (
            <div key={category.id}>
              <h2 className="text-lg font-bold text-gray-800 mb-3">{category.name}</h2>
              <div className="grid grid-cols-1 gap-4">
                
                {/* ✅ FIX: Loop through 'menu_items' */}
                {(category.menu_items || []).map(item => (
                  <div key={item.id} className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex justify-between items-center">
                    
                    {/* Item Details */}
                    <div className="flex-1">
                      <h3 className="font-bold text-gray-800">{item.name}</h3>
                      {/* Description might be missing, so we handle that */}
                      {item.description && (
                         <p className="text-xs text-gray-400 line-clamp-1">{item.description}</p>
                      )}
                      <p className="text-blue-600 font-bold mt-1">₹{item.price}</p>
                    </div>

                    {/* Quantity Controls */}
                    <div className="flex flex-col items-center gap-1 ml-4">
                      {getItemQuantity(item.id) > 0 ? (
                        <div className="flex items-center gap-2 bg-blue-50 rounded-lg p-1">
                          <button 
                            onClick={() => removeFromCart(item.id)}
                            className="w-8 h-8 bg-white border border-blue-200 text-blue-600 rounded-lg font-bold"
                          >
                            -
                          </button>
                          <span className="text-sm font-bold min-w-[20px] text-center">
                            {getItemQuantity(item.id)}
                          </span>
                          <button 
                            onClick={() => addToCart(item)}
                            className="w-8 h-8 bg-blue-600 text-white rounded-lg font-bold"
                          >
                            +
                          </button>
                        </div>
                      ) : (
                        <button 
                          onClick={() => addToCart(item)}
                          className="w-10 h-10 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl flex items-center justify-center text-lg"
                        >
                          ➕
                        </button>
                      )}
                    </div>
                  </div>
                ))}

                {/* Empty State for Category */}
                {(!category.menu_items || category.menu_items.length === 0) && (
                   <div className="text-center p-4 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                      <p className="text-gray-400 text-sm">No items in this category yet.</p>
                   </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Floating Cart Footer */}
      {Object.keys(cart).length > 0 && (
        <div className="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-20">
          <div className="flex justify-between items-center mb-3">
            <span className="text-gray-500 text-sm font-medium">{Object.keys(cart).length} Items Selected</span>
            <span className="text-xl font-bold text-gray-900">₹{getTotalPrice().toFixed(2)}</span>
          </div>
          <button 
            onClick={placeOrder}
            disabled={submitting}
            className="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg active:scale-95 transition-all flex justify-center items-center gap-2"
          >
            {submitting ? (
              <span>Sending Order... ⏳</span>
            ) : (
              <span>Confirm Order ✅</span>
            )}
          </button>
        </div>
      )}
    </div>
  );
}