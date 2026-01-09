'use client';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useParams, useRouter } from 'next/navigation';

export default function CustomerMenu() {
  const params = useParams(); // Get IDs from URL
  const router = useRouter();
  
  const [categories, setCategories] = useState([]);
  const [cart, setCart] = useState({}); // { itemId: quantity }
  const [customerName, setCustomerName] = useState('');
  const [isCheckout, setIsCheckout] = useState(false);

  // 1. Fetch Menu on Load
  useEffect(() => {
    if(params.restaurantId) {
      axios.get(`${process.env.NEXT_PUBLIC_API_URL}/customer/menu/${params.restaurantId}`)
        .then(res => setCategories(res.data))
        .catch(err => console.error(err));
    }
  }, [params.restaurantId]);

  // 2. Add to Cart Logic
  const addToCart = (itemId) => {
    setCart(prev => ({ ...prev, [itemId]: (prev[itemId] || 0) + 1 }));
  };

  const removeFromCart = (itemId) => {
    setCart(prev => {
      const newCart = { ...prev };
      if (newCart[itemId] > 1) newCart[itemId]--;
      else delete newCart[itemId];
      return newCart;
    });
  };

  // 3. Calculate Total
  const getTotal = () => {
    let total = 0;
    categories.forEach(cat => {
      cat.menu_items.forEach(item => {
        if(cart[item.id]) total += item.price * cart[item.id];
      });
    });
    return total;
  };

  // 4. Place Order Logic
  const placeOrder = async () => {
    if(!customerName) return alert("Please enter your name");
    
    // Format cart for API
    const itemsPayload = Object.keys(cart).map(itemId => ({
      id: itemId,
      quantity: cart[itemId]
    }));

    try {
      await axios.post(`${process.env.NEXT_PUBLIC_API_URL}/customer/order`, {
        restaurant_id: params.restaurantId,
        table_id: params.tableId,
        customer_name: customerName,
        items: itemsPayload
      });
      alert("Order Placed Successfully! 🍲");
      setCart({});
      setIsCheckout(false);
    } catch (error) {
      alert("Failed to place order. Try again.");
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 pb-20">
      {/* Header */}
      <div className="bg-white p-4 shadow sticky top-0 z-10">
        <h1 className="text-xl font-bold text-center">🍽️ Digital Menu</h1>
        <p className="text-center text-sm text-gray-500">Table #{params.tableId}</p>
      </div>

      {/* Menu List */}
      <div className="p-4 space-y-6">
        {categories.map(category => (
          <div key={category.id}>
            <h2 className="text-lg font-bold text-gray-800 mb-3 border-l-4 border-orange-500 pl-2">
              {category.name}
            </h2>
            <div className="space-y-4">
              {category.menu_items.map(item => (
                <div key={item.id} className="bg-white p-4 rounded-lg shadow flex justify-between items-center">
                  <div>
                    <h3 className="font-semibold">{item.name}</h3>
                    <p className="text-orange-600 font-bold">${item.price}</p>
                  </div>
                  
                  {/* Add Button */}
                  {cart[item.id] ? (
                    <div className="flex items-center gap-3 bg-gray-100 rounded-full px-3 py-1">
                      <button onClick={() => removeFromCart(item.id)} className="text-red-500 font-bold">-</button>
                      <span className="font-semibold">{cart[item.id]}</span>
                      <button onClick={() => addToCart(item.id)} className="text-green-600 font-bold">+</button>
                    </div>
                  ) : (
                    <button 
                      onClick={() => addToCart(item.id)}
                      className="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow hover:bg-orange-600"
                    >
                      ADD
                    </button>
                  )}
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* Footer Cart Bar */}
      {Object.keys(cart).length > 0 && (
        <div className="fixed bottom-0 w-full bg-white border-t p-4 shadow-lg z-20">
          <div className="flex justify-between items-center mb-4">
            <span className="text-lg font-bold">Total: ${getTotal().toFixed(2)}</span>
            <button 
              onClick={() => setIsCheckout(true)}
              className="bg-green-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg"
            >
              View Cart & Order
            </button>
          </div>
        </div>
      )}

      {/* Checkout Modal */}
      {isCheckout && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl w-full max-w-sm p-6">
            <h2 className="text-xl font-bold mb-4">Confirm Order</h2>
            
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
              <input 
                type="text" 
                value={customerName}
                onChange={(e) => setCustomerName(e.target.value)}
                placeholder="Enter your name"
                className="w-full border p-2 rounded"
              />
            </div>

            <div className="flex gap-3">
              <button 
                onClick={() => setIsCheckout(false)}
                className="flex-1 bg-gray-200 py-2 rounded-lg font-semibold"
              >
                Cancel
              </button>
              <button 
                onClick={placeOrder}
                className="flex-1 bg-green-600 text-white py-2 rounded-lg font-semibold"
              >
                Place Order
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}