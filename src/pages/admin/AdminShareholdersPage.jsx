import React, { useState, useEffect } from 'react';
import { getAllShareholders, deleteShareholder, getShareholderStats } from '@/services/shareholderService';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/use-toast';
import { Plus, Search, Edit, Trash2, PieChart } from 'lucide-react';
import ShareholderForm from '@/components/admin/ShareholderForm';
import { supabase } from "@/lib/customSupabaseClient";

const AdminShareholdersPage = () => {
  const [shareholders, setShareholders] = useState([]);
  const [stats, setStats] = useState({ total: 100, assigned: 0, remaining: 100 });
  const [search, setSearch] = useState('');
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [editFormData, setEditFormData] = useState({});
  const { toast } = useToast();

  const loadData = async () => {
    const data = await getAllShareholders();
    const s = await getShareholderStats();
    setShareholders(data);
    setStats(s);
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleDelete = async (id) => {
    if (confirm("Are you sure you want to delete this shareholder? This will release their shares.")) {
      try {
        await deleteShareholder(id);
        toast({ title: "Deleted", description: "Shareholder removed." });
        loadData();
      } catch (error) {
        toast({ title: "Error", description: error.message, variant: "destructive" });
      }
    }
  };

  const handleCreate = () => {
    setEditingItem(null);
    setIsFormModalOpen(true);
  };

  const openExtendedEdit = (item) => {
    setEditingItem(item);
    setEditFormData({
        ...item,
        payment_methods: item.payment_methods || [],
        price_per_share: item.price_per_share || ''
    });
    setIsEditModalOpen(true);
  };

  const handleExtendedEditChange = (e) => {
      const { name, value } = e.target;
      setEditFormData(p => ({ ...p, [name]: value }));
  };

  const handlePaymentMethodToggle = (method) => {
      setEditFormData(prev => {
          const current = prev.payment_methods || [];
          if (current.includes(method)) {
              return { ...prev, payment_methods: current.filter(m => m !== method) };
          } else {
              return { ...prev, payment_methods: [...current, method] };
          }
      });
  };

  const submitExtendedEdit = async () => {
      try {
          const { error } = await supabase
            .from('shareholders')
            .update({
                name: editFormData.name,
                email: editFormData.email,
                phone: editFormData.phone,
                shares_assigned: parseInt(editFormData.shares_assigned),
                price_per_share: editFormData.price_per_share,
                updated_at: new Date().toISOString()
            })
            .eq('id', editingItem.id);

          if (error) throw error;
          
          toast({ title: "Updated", description: "Shareholder details updated." });
          setIsEditModalOpen(false);
          loadData();
      } catch (error) {
          toast({ title: "Error", description: error.message, variant: "destructive" });
      }
  };

  const handleFormSuccess = () => {
    setIsFormModalOpen(false);
    loadData();
  };

  const filtered = shareholders.filter(s => 
    (s.name || '').toLowerCase().includes(search.toLowerCase()) || 
    (s.email || '').toLowerCase().includes(search.toLowerCase())
  );

  const percentage = stats.total > 0 ? (stats.assigned / stats.total) * 100 : 0;

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Shareholders</h1>
          <p className="text-gray-500">Manage company equity and shareholder registry</p>
        </div>
        
        <Button onClick={handleCreate} className="bg-[#D4AF37] text-[#003D82] hover:bg-[#b5952f] font-bold">
            <Plus className="w-4 h-4 mr-2" /> Add Shareholder
        </Button>
      </div>

      {/* Stats Card */}
      <Card className="bg-gradient-to-r from-[#003D82] to-[#00509d] text-white border-none shadow-xl">
        <CardContent className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <PieChart className="w-6 h-6 text-[#D4AF37]" />
              <h3 className="font-bold text-lg">Share Allocation</h3>
            </div>
            <span className="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">
              Total Shares: {stats.total}
            </span>
          </div>
          
          <div className="mb-2 flex justify-between text-sm">
            <span>Assigned: {stats.assigned}</span>
            <span className="text-[#D4AF37] font-bold">Remaining: {stats.remaining}</span>
          </div>
          
          <div className="w-full bg-black/30 rounded-full h-3 overflow-hidden">
            <div 
              className="bg-[#D4AF37] h-full transition-all duration-500"
              style={{ width: `${percentage}%` }}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-400" />
            <Input 
              placeholder="Search by name or email..." 
              className="pl-8" 
              value={search} 
              onChange={e => setSearch(e.target.value)} 
            />
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th className="p-4 text-left font-semibold text-gray-600">Name</th>
                  <th className="p-4 text-left font-semibold text-gray-600">Shares</th>
                  <th className="p-4 text-left font-semibold text-gray-600">Contact</th>
                  <th className="p-4 text-left font-semibold text-gray-600">Status</th>
                  <th className="p-4 text-right font-semibold text-gray-600">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filtered.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="p-8 text-center text-gray-500">No shareholders found.</td>
                  </tr>
                ) : (
                  filtered.map(s => (
                    <tr key={s.id} className="border-b hover:bg-gray-50 transition-colors">
                      <td className="p-4 font-medium text-[#003D82]">{s.name}</td>
                      <td className="p-4">
                        <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                          {s.shares_assigned} Shares
                        </Badge>
                      </td>
                      <td className="p-4 text-gray-600">
                        <div className="text-xs">{s.email}</div>
                        <div className="text-xs">{s.phone}</div>
                      </td>
                      <td className="p-4">
                        <Badge className={`${
                          (s.payment_status || '').toLowerCase() === 'paid' || (s.payment_status || '').toLowerCase() === 'completed' ? 'bg-green-100 text-green-700' : 
                          'bg-yellow-100 text-yellow-700'
                        }`}>
                          {s.payment_status || 'Pending'}
                        </Badge>
                      </td>
                      <td className="p-4 text-right space-x-2">
                        <Button variant="ghost" size="sm" onClick={() => openExtendedEdit(s)}>
                          <Edit className="w-4 h-4 text-gray-500" />
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => handleDelete(s.id)}>
                          <Trash2 className="w-4 h-4 text-red-500" />
                        </Button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {/* Add/Basic Edit Modal */}
      <Dialog open={isFormModalOpen} onOpenChange={setIsFormModalOpen}>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>New Shareholder</DialogTitle>
            </DialogHeader>
            <ShareholderForm 
              initialData={editingItem} 
              onSuccess={handleFormSuccess} 
              onCancel={() => setIsFormModalOpen(false)} 
            />
          </DialogContent>
      </Dialog>

      {/* Extended Edit Modal */}
      <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
          <DialogContent className="max-w-xl">
              <DialogHeader><DialogTitle>Edit Shareholder Details</DialogTitle></DialogHeader>
              <div className="space-y-4 py-4">
                  <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2"><Label>Full Name</Label><Input name="name" value={editFormData.name} onChange={handleExtendedEditChange} /></div>
                      <div className="space-y-2"><Label>Phone</Label><Input name="phone" value={editFormData.phone} onChange={handleExtendedEditChange} /></div>
                  </div>
                  <div className="space-y-2"><Label>Email</Label><Input name="email" value={editFormData.email} onChange={handleExtendedEditChange} /></div>
                  
                  <div className="grid grid-cols-2 gap-4 border-t pt-4">
                      <div className="space-y-2">
                          <Label>Shares Assigned</Label>
                          <Input type="number" name="shares_assigned" value={editFormData.shares_assigned} onChange={handleExtendedEditChange} />
                          <p className="text-xs text-gray-500">Max available: {stats.remaining + parseInt(editingItem?.shares_assigned || 0)}</p>
                      </div>
                      <div className="space-y-2">
                          <Label>Price Per Share</Label>
                          <Input name="price_per_share" value={editFormData.price_per_share} onChange={handleExtendedEditChange} placeholder="e.g. 1000 USD" />
                      </div>
                  </div>

                  <div className="space-y-3 border-t pt-4">
                      <Label>Payment Methods</Label>
                      <div className="flex gap-6">
                          {['VISA', 'Mobile Money', 'Pay Later'].map(method => (
                              <div key={method} className="flex items-center space-x-2">
                                  <Checkbox 
                                    id={`pm-${method}`} 
                                    checked={editFormData.payment_methods?.includes(method)}
                                    onCheckedChange={() => handlePaymentMethodToggle(method)}
                                  />
                                  <Label htmlFor={`pm-${method}`}>{method}</Label>
                              </div>
                          ))}
                      </div>
                  </div>
              </div>
              <DialogFooter>
                  <Button variant="outline" onClick={() => setIsEditModalOpen(false)}>Cancel</Button>
                  <Button onClick={submitExtendedEdit} className="bg-[#003D82]">Save Changes</Button>
              </DialogFooter>
          </DialogContent>
      </Dialog>
    </div>
  );
};

export default AdminShareholdersPage;