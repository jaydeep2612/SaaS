'use client';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useRouter } from 'next/navigation';

export default function Dashboard() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  // 1. Fetch Orders on Load
  useEffect(() => {
    const fetchOrders = async () => {
      const restaurantId = localStorage.getItem('restaurant_id');
      if (!restaurantId) return router.push('/'); 

      try {
        const res = await axios.get(`${process.env.NEXT_PUBLIC_API_URL}/waiter/orders?restaurant_id=${restaurantId}`);
        setOrders(res.data);
      } catch (error) {
        console.error("Failed to fetch orders");
      } finally {
        setLoading(false);
      }
    };

    fetchOrders();
    // Poll every 5 seconds
    const interval = setInterval(fetchOrders, 5000); 
    return () => clearInterval(interval);
  }, []);

  // 2. Handle "Serve" Button Click
  const markServed = async (orderId) => {
    try {
      await axios.post(`${process.env.NEXT_PUBLIC_API_URL}/waiter/order/${orderId}/serve`);
      setOrders(orders.filter(order => order.id !== orderId));
    } catch (error) {
      alert("Error updating order");
    }
  };

  if (loading) return <div className="p-10 text-center">Loading Ready Orders...</div>;

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold text-gray-800">🔔 Ready to Serve</h1>
        <button onClick={() => { localStorage.clear(); router.push('/'); }} className="text-red-500 font-medium hover:text-red-700 transition">Logout</button>
      </div>

      {orders.length === 0 ? (
        <div className="text-center mt-20">
            <div className="text-6xl mb-4">☕</div>
            <p className="text-gray-500 text-lg">No orders are ready right now.</p>
            <p className="text-gray-400 text-sm">Relax for a moment!</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {orders.map((order) => (
            <div key={order.id} className="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-500 flex flex-col justify-between">
              
              {/* Header */}
              <div className="flex justify-between items-start mb-4">
                <div>
                  {/* Table Number */}
                  <h2 className="text-2xl font-bold text-gray-800">
                    Table {order.table?.table_number || order.table_id || 'N/A'}
                  </h2>
                  
                  {/* ✅ ADDED: Customer Name Display */}
                  <p className="text-lg font-semibold text-blue-600 mt-1 flex items-center gap-1">
                    👤 {order.customer_name || 'Guest'}
                  </p>

                  <p className="text-xs text-gray-400 mt-1">Order #{order.id}</p>
                </div>
                <span className="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium animate-pulse">
                  Ready
                </span>
              </div>

              {/* Items List */}
              <div className="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                <ul className="space-y-2">
                  {order.items.map((item, index) => (
                    <li key={index} className="flex justify-between text-gray-700 text-sm">
                      <span className="flex-1">
                        <span className="font-bold text-gray-900 bg-gray-200 px-2 py-0.5 rounded text-xs mr-2">
                            {item.quantity}x
                        </span> 
                        {item.menu_item?.name || 'Unknown Item'}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Action Button */}
              <button 
                onClick={() => markServed(order.id)}
                className="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg transform active:scale-95"
              >
                ✅ Mark as Served
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}