'use client';
import { useState } from 'react';
import axios from 'axios';
import { useRouter } from 'next/navigation';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const router = useRouter();

  const handleLogin = async (e) => {
    e.preventDefault();
    try {
      // 1. Call Laravel API
      const res = await axios.post(`${process.env.NEXT_PUBLIC_API_URL}/waiter/login`, {
        email,
        password
      });

      // 2. Save User Info to LocalStorage (Simple Auth)
      localStorage.setItem('restaurant_id', res.data.user.restaurant_id);
      localStorage.setItem('waiter_name', res.data.user.name);
      
      // 3. Go to Dashboard
      router.push('/dashboard');
    } catch (err) {
      console.error("LOGIN ERROR:", err);
    }
  };

  return (
    <div className="flex items-center justify-center h-screen bg-gray-100">
      <form onSubmit={handleLogin} className="p-8 bg-white rounded shadow-md w-96">
        <h1 className="text-2xl font-bold mb-6 text-center text-blue-600">Waiter Login</h1>
        
        {error && <p className="text-red-500 text-sm mb-4">{error}</p>}

        <input 
          type="email" 
          placeholder="Email" 
          className="w-full p-2 border rounded mb-4"
          value={email} onChange={(e) => setEmail(e.target.value)} required 
        />
        <input 
          type="password" 
          placeholder="Password" 
          className="w-full p-2 border rounded mb-6"
          value={password} onChange={(e) => setPassword(e.target.value)} required 
        />
        
        <button className="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
          Login
        </button>
      </form>
    </div>
  );
}