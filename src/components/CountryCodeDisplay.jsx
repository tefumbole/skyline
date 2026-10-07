import React from 'react';

const CountryCodeDisplay = ({ country, phone, className }) => {
  const displayPhone = phone ? phone.replace(country?.code || '', '').trim() : '';
  
  return (
    <div className={`flex flex-col ${className}`}>
        <span className="text-xs text-gray-500 uppercase font-bold tracking-wider">
            {country || 'Unknown Country'}
        </span>
        <span className="font-mono text-gray-700">
            {phone || 'N/A'}
        </span>
    </div>
  );
};

export default CountryCodeDisplay;