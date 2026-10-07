import React, { useState, useEffect } from 'react';
import { getShares, createShare, updateShare, deleteShare } from '@/services/sharesService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Trash2, Edit2, Plus, Loader2, DollarSign, PieChart } from 'lucide-react';

const AdminSharesPage = () => {
  const [shares, setShares] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingShare, setEditingShare] = useState(null);
  const [formData, setFormData] = useState({ name: '', totalShares: '', price: '' });
  const { toast } = useToast();

  const fetchShares = async () => {
    setLoading(true);
    const data = await getShares();
    setShares(data);
    setLoading(false);
  };

  useEffect(() => {
    fetchShares();
  }, []);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async () => {
    if (!formData.name || !formData.totalShares || !formData.price) {
      toast({ title: "Error", description: "All fields are required.", variant: "destructive" });
      return;
    }

    try {
      if (editingShare) {
        await updateShare(editingShare.id, formData);
        toast({ title: "Success", description: "Share updated successfully." });
      } else {
        await createShare(formData);
        toast({ title: "Success", description: "Share created successfully." });
      }
      setModalOpen(false);
      fetchShares();
    } catch (error) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    }
  };

  const handleEdit = (share) => {
    setEditingShare(share);
    setFormData({ name: share.name, totalShares: share.totalShares, price: share.price });
    setModalOpen(true);
  };

  const handleDelete = async (id) => {
    if (window.confirm("Are you sure you want to delete this share category?")) {
      await deleteShare(id);
      toast({ title: "Success", description: "Share deleted." });
      fetchShares();
    }
  };

  const openNewShareModal = () => {
    setEditingShare(null);
    setFormData({ name: '', totalShares: '', price: '' });
    setModalOpen(true);
  };

  return (
    <div className="p-6 bg-gray-50 min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Shares Management</h1>
          <p className="text-gray-500">Configure share batches, pricing, and availability.</p>
        </div>
        <Button onClick={openNewShareModal} className="bg-[#D4AF37] text-[#003D82] hover:bg-[#bfa030] font-bold">
          <Plus className="mr-2 h-4 w-4" /> Create Share Batch
        </Button>
      </div>

      {loading ? (
        <div className="flex justify-center p-12"><Loader2 className="h-8 w-8 animate-spin text-[#003D82]" /></div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {shares.map(share => (
             <div key={share.id} className="bg-white p-6 rounded-lg shadow-md border border-gray-100 hover:shadow-lg transition-shadow">
                <div className="flex justify-between items-start mb-4">
                    <div>
                        <h3 className="text-xl font-bold text-[#003D82]">{share.name}</h3>
                        <p className="text-xs text-gray-500">ID: {share.id}</p>
                    </div>
                    <div className="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold flex items-center">
                        <DollarSign className="w-3 h-3 mr-1" /> {share.price}
                    </div>
                </div>
                
                <div className="space-y-3 mb-6">
                    <div className="flex justify-between items-center text-sm">
                        <span className="text-gray-500">Total Shares:</span>
                        <span className="font-semibold">{share.totalShares}</span>
                    </div>
                    <div className="flex justify-between items-center text-sm">
                        <span className="text-gray-500">Remaining:</span>
                        <span className="font-bold text-[#D4AF37]">{share.remainingShares}</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                        <div className="bg-[#003D82] h-2.5 rounded-full" style={{ width: `${((share.totalShares - share.remainingShares) / share.totalShares) * 100}%` }}></div>
                    </div>
                    <p className="text-xs text-right text-gray-400">
                        {Math.round(((share.totalShares - share.remainingShares) / share.totalShares) * 100)}% Sold
                    </p>
                </div>

                <div className="flex gap-2">
                    <Button variant="outline" className="flex-1" onClick={() => handleEdit(share)}>Edit</Button>
                    <Button variant="destructive" size="icon" onClick={() => handleDelete(share.id)}><Trash2 className="h-4 w-4" /></Button>
                </div>
             </div>
          ))}
          {shares.length === 0 && <p className="col-span-3 text-center text-gray-500 py-10">No share batches found.</p>}
        </div>
      )}

      <Dialog open={modalOpen} onOpenChange={setModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editingShare ? 'Edit Share Batch' : 'Create New Share Batch'}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div>
                <label className="text-sm font-medium mb-1 block">Share Batch Name</label>
                <Input name="name" value={formData.name} onChange={handleInputChange} placeholder="e.g. Series A" />
            </div>
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="text-sm font-medium mb-1 block">Total Shares</label>
                    <Input type="number" name="totalShares" value={formData.totalShares} onChange={handleInputChange} placeholder="1000" />
                </div>
                <div>
                    <label className="text-sm font-medium mb-1 block">Price per Share ($)</label>
                    <Input type="number" name="price" value={formData.price} onChange={handleInputChange} placeholder="500" />
                </div>
            </div>
            
            <Button onClick={handleSubmit} className="w-full bg-[#003D82] hover:bg-[#002d62]">
                {editingShare ? 'Update Share' : 'Create Share'}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default AdminSharesPage;