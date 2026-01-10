'use client';
import { useState, useEffect, use } from 'react'; // 👈 Import 'use'
import axios from 'axios';
import { useRouter } from 'next/navigation';

export default function CheckInPage({ params }) {
  // 1. Unwrap params using React.use()
  const { restaurantId, tableId } = use(params); 

  const router = useRouter();
  const [isLoading, setIsLoading] = useState(true);
  const [status, setStatus] = useState('available');
  const [occupantName, setOccupantName] = useState('');
  const [customerName, setCustomerName] = useState('');

  // 2. Check Table Status on Load
  useEffect(() => {
    // Safety check: Ensure IDs exist before calling API
    if (!tableId || !restaurantId) return; 

    const checkStatus = async () => {
      try {
        const res = await axios.get(`${process.env.NEXT_PUBLIC_API_URL}/table/${tableId}/status`);
        
        if (res.data.status === 'occupied') {
          const storedName = localStorage.getItem('customer_name');
          const storedTable = localStorage.getItem('table_id');

          if (storedName && storedTable === tableId) {
            router.push(`/menu/${restaurantId}/${tableId}/order`);
          } else {
            setStatus('occupied');
            setOccupantName(res.data.customer_name);
          }
        }
      } catch (error) {
        console.error("Error fetching status:", error);
      } finally {
        setIsLoading(false);
      }
    };

    checkStatus();
  }, [tableId, restaurantId, router]); // 👈 Dependencies are now safe

  // 3. Handle Check-In
  const handleCheckIn = async (e) => {
    e.preventDefault();
    if (!customerName.trim()) return;

    try {
      await axios.post(`${process.env.NEXT_PUBLIC_API_URL}/table/${tableId}/occupy`, {
        customer_name: customerName
      });

      localStorage.setItem('customer_name', customerName);
      localStorage.setItem('table_id', tableId);
      localStorage.setItem('restaurant_id', restaurantId);

      router.push(`/menu/${restaurantId}/${tableId}/order`);

    } catch (error) {
      alert("Failed to check in. Please try again.");
    }
  };

  if (isLoading) return <div className="p-10 text-center">Checking Table Status...</div>;

  if (status === 'occupied') {
    return (
      <div className="flex flex-col items-center justify-center h-screen bg-red-50 p-6 text-center">
        <div className="text-6xl mb-4">🚫</div>
        <h1 className="text-2xl font-bold text-red-600">Table Occupied</h1>
        <p className="mt-2 text-gray-600">
          This table is currently occupied by <strong>{occupantName}</strong>.
        </p>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center justify-center h-screen bg-gray-50 p-6">
      <div className="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm">
        <div className="text-center mb-6">
          <span className="text-4xl">🍽️</span>
          <h1 className="text-2xl font-bold mt-3 text-gray-800">Welcome!</h1>
          <p className="text-gray-500 text-sm">Please enter your name to begin.</p>
        </div>

        <form onSubmit={handleCheckIn}>
          <div className="mb-4">
            <label className="block text-gray-700 text-sm font-bold mb-2">Your Name</label>
            <input 
              type="text" 
              className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder=""
              value={customerName}
              onChange={(e) => setCustomerName(e.target.value)}
              required
            />
          </div>
          
          <button 
            type="submit" 
            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition duration-200"
          >
            Start Ordering 🚀
          </button>
        </form>
      </div>
    </div>
  );
}