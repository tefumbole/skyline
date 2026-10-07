import React from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export const countryOptions = [
  { code: '+250', country: 'RW', label: 'Rwanda (+250)', placeholder: '788 123 456' },
  { code: '+254', country: 'KE', label: 'Kenya (+254)', placeholder: '712 345 678' },
  { code: '+256', country: 'UG', label: 'Uganda (+256)', placeholder: '772 123 456' },
  { code: '+255', country: 'TZ', label: 'Tanzania (+255)', placeholder: '754 123 456' },
  { code: '+1', country: 'US', label: 'USA/Canada (+1)', placeholder: '202 555 0123' },
  { code: '+44', country: 'UK', label: 'UK (+44)', placeholder: '7911 123456' },
  { code: '+27', country: 'ZA', label: 'South Africa (+27)', placeholder: '83 123 4567' },
  { code: '+234', country: 'NG', label: 'Nigeria (+234)', placeholder: '803 123 4567' },
];

const CountryCodeSelector = ({ value, onChange, disabled }) => {
  return (
    <Select value={value} onValueChange={onChange} disabled={disabled}>
      <SelectTrigger className="w-[140px] bg-gray-50">
        <SelectValue placeholder="Code" />
      </SelectTrigger>
      <SelectContent>
        {countryOptions.map((option) => (
          <SelectItem key={option.code} value={option.code}>
            <span className="flex items-center gap-2">
              <span className="font-medium">{option.code}</span>
              <span className="text-muted-foreground text-xs hidden sm:inline">({option.country})</span>
            </span>
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
};

export default CountryCodeSelector;