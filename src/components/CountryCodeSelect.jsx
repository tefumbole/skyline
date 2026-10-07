import React from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const countries = [
  { name: 'Rwanda', code: '+250', flag: '🇷🇼' },
  { name: 'Uganda', code: '+256', flag: '🇺🇬' },
  { name: 'Cameroon', code: '+237', flag: '🇨🇲' },
  { name: 'Kenya', code: '+254', flag: '🇰🇪' },
  { name: 'Nigeria', code: '+234', flag: '🇳🇬' },
  { name: 'United Kingdom', code: '+44', flag: '🇬🇧' },
  { name: 'United States', code: '+1', flag: '🇺🇸' },
];

const CountryCodeSelect = ({ value, onChange, className }) => {
  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger className={`w-[200px] ${className}`}>
        <SelectValue placeholder="Select Country" />
      </SelectTrigger>
      <SelectContent>
        {countries.map((country) => (
          <SelectItem key={country.code} value={country.code}>
            <span className="flex items-center gap-2">
              <span className="text-lg">{country.flag}</span>
              <span className="text-gray-900 font-medium">{country.name}</span>
              <span className="text-gray-500 font-normal">({country.code})</span>
            </span>
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
};

export default CountryCodeSelect;