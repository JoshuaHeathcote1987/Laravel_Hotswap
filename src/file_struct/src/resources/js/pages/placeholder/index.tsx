import React from 'react';
import Logo from '../../../../../logo.png';

export default function Placeholder() {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
      <img src={Logo} alt="Hotswap Logo" className="w-64 h-64 mb-8" />
    </div>
  );
}
