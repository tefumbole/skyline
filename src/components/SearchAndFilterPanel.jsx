import React from 'react';
import { Input } from '@/components/ui/input';
import { Search, Filter, Calendar } from 'lucide-react';

const SearchAndFilterPanel = ({ filters, setFilters }) => {
  
  const modules = [
    "Strategic Leadership & Governance",
    "Advanced Financial Management",
    "Project Management Professional (PMP)",
    "Digital Transformation & Innovation",
    "Human Resource Management",
    "Supply Chain & Logistics",
    "Risk Management & Compliance",
    "Marketing Strategy & Branding"
  ];

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  return (
    <div className="bg-white p-4 rounded-lg border shadow-sm space-y-4 md:space-y-0 md:flex md:items-end md:gap-4 mb-6">
      
      {/* Search */}
      <div className="flex-1 space-y-1">
        <label className="text-sm font-medium text-gray-700">Search Students</label>
        <div className="relative">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
          <Input 
            name="search"
            placeholder="Name, email, or phone..." 
            className="pl-9"
            value={filters.search}
            onChange={handleInputChange}
          />
        </div>
      </div>

      {/* Module Filter */}
      <div className="w-full md:w-64 space-y-1">
        <label className="text-sm font-medium text-gray-700">Module</label>
        <div className="relative">
          <select
            name="module"
            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            value={filters.module}
            onChange={handleInputChange}
          >
            <option value="all">All Modules</option>
            {modules.map((mod, idx) => (
              <option key={idx} value={mod}>{mod}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Status Filter */}
      <div className="w-full md:w-48 space-y-1">
        <label className="text-sm font-medium text-gray-700">Payment Status</label>
        <select
          name="status"
          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          value={filters.status}
          onChange={handleInputChange}
        >
          <option value="all">All Statuses</option>
          <option value="paid">Paid</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
        </select>
      </div>
    </div>
  );
};

export default SearchAndFilterPanel;