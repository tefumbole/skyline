import React, { useEffect, useState } from 'react';
import { getMasterClassRegistrations, updatePaymentStatus } from '@/services/masterClassService';
import StudentRegistryTable from '@/components/StudentRegistryTable';
import SearchAndFilterPanel from '@/components/SearchAndFilterPanel';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { Check } from 'lucide-react';

const AdminPaymentsPage = () => {
  const [allData, setAllData] = useState([]);
  const [filteredData, setFilteredData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    search: '',
    module: 'all',
    status: 'all'
  });
  const { toast } = useToast();

  const fetchData = async () => {
    setLoading(true);
    const { data } = await getMasterClassRegistrations();
    if (data) {
      setAllData(data);
      applyFilters(data, filters);
    }
    setLoading(false);
  };

  const applyFilters = (data, currentFilters) => {
      let result = [...data];
      if (currentFilters.search) {
         const q = currentFilters.search.toLowerCase();
         result = result.filter(item => item.full_name?.toLowerCase().includes(q) || item.email?.toLowerCase().includes(q));
      }
      if (currentFilters.module !== 'all') result = result.filter(item => item.module === currentFilters.module);
      if (currentFilters.status !== 'all') result = result.filter(item => item.payment_status === currentFilters.status);
      setFilteredData(result);
  };

  useEffect(() => {
    fetchData();
  }, []);

  useEffect(() => {
    applyFilters(allData, filters);
  }, [filters, allData]);

  const handleMarkAsPaid = async (id) => {
      try {
          const res = await updatePaymentStatus(id, 'completed', `MANUAL-ADMIN-${Date.now()}`);
          if (res.data) {
              toast({ title: "Success", description: "Payment marked as completed and notification sent." });
              fetchData();
          } else {
              throw new Error(res.error?.message);
          }
      } catch (err) {
          toast({ variant: "destructive", title: "Error", description: err.message || "Failed to update status" });
      }
  };

  const totalRevenue = filteredData
    .filter(r => r.payment_status === 'completed' || r.payment_status === 'paid')
    .reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);

  // Custom Columns or Render for the generic table if we wanted to inject the button?
  // Since StudentRegistryTable is generic, we might need to modify it or create a specific table here. 
  // Given constraints, I'll render a specific table for payments here instead of reusing the generic one if it lacks the specific action button logic.
  // Actually, let's create a dedicated table view for payments here to ensure the "Mark as Paid" button exists.

  return (
    <div className="space-y-6">
       <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#003366]">Payment Management</h1>
          <p className="text-gray-500">Track and verify student payments</p>
        </div>
        <Card className="w-full md:w-auto bg-[#003366] text-white border-none">
            <CardContent className="p-4 flex items-center gap-4">
                <div className="text-sm font-light opacity-80">Total Revenue<br/>(Visible Rows)</div>
                <div className="text-2xl font-bold text-[#D4AF37]">${totalRevenue.toLocaleString()}</div>
            </CardContent>
        </Card>
      </div>

      <SearchAndFilterPanel filters={filters} setFilters={setFilters} />

      <div className="rounded-md border bg-white overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
                <thead className="bg-gray-50 border-b text-xs uppercase text-gray-700">
                    <tr>
                        <th className="px-6 py-3">Student</th>
                        <th className="px-6 py-3">Module</th>
                        <th className="px-6 py-3">Method</th>
                        <th className="px-6 py-3">Status</th>
                        <th className="px-6 py-3">Amount</th>
                        <th className="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {loading ? (
                        <tr><td colSpan="6" className="p-8 text-center">Loading...</td></tr>
                    ) : filteredData.length === 0 ? (
                        <tr><td colSpan="6" className="p-8 text-center text-gray-500">No records found.</td></tr>
                    ) : (
                        filteredData.map((item) => (
                            <tr key={item.id} className="border-b hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="font-medium text-navy">{item.full_name}</div>
                                    <div className="text-xs text-gray-500">{item.email}</div>
                                </td>
                                <td className="px-6 py-4">{item.module}</td>
                                <td className="px-6 py-4 capitalize">{(item.payment_method || '-').replace('_', ' ')}</td>
                                <td className="px-6 py-4">
                                    <Badge 
                                        variant={item.payment_status === 'completed' || item.payment_status === 'paid' ? 'success' : 'outline'}
                                        className={
                                            item.payment_status === 'pending_payment' ? 'bg-yellow-100 text-yellow-800 border-none' : 
                                            item.payment_status === 'completed' ? 'bg-green-100 text-green-800 border-none' : ''
                                        }
                                    >
                                        {(item.payment_status || 'pending').replace('_', ' ')}
                                    </Badge>
                                </td>
                                <td className="px-6 py-4">${item.amount}</td>
                                <td className="px-6 py-4 text-right">
                                    {item.payment_status === 'pending_payment' && (
                                        <Button 
                                            size="sm" 
                                            className="bg-green-600 hover:bg-green-700 text-white"
                                            onClick={() => handleMarkAsPaid(item.id)}
                                        >
                                            <Check className="w-4 h-4 mr-1" /> Mark Paid
                                        </Button>
                                    )}
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
      </div>
    </div>
  );
};

export default AdminPaymentsPage;