import React from 'react';

export default function Placeholder() {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-gray-50 to-gray-200">
      <h1 className="text-8xl font-extrabold drop-shadow-lg animate-pulse bg-gradient-to-r from-red-600 via-orange-500 to-yellow-400 bg-clip-text text-transparent">
        Hotswap
      </h1>
      <p className="mt-6 text-xl text-gray-700">Your Reactjs module is ready to go!</p>
    </div>
  );
}
