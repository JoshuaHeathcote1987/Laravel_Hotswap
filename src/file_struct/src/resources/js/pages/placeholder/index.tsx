import React from 'react';

export default function Placeholder() {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-gray-50 to-gray-200">
      <h1 className="text-8xl font-extrabold text-indigo-600 drop-shadow-lg animate-pulse">
        Hotswap
      </h1>
      <p className="mt-6 text-xl text-gray-700">Your module is ready to go!</p>
    </div>
  );
}
