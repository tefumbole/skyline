import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { saveShareholderRegistration, getShareholderStats } from '@/services/shareholderService';

const ShareholderForm = ({ initialData, onSuccess, onCancel }) => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    shares_assigned: 0,
    payment_status: 'Pay Later'
  });
  const [stats, setStats] = useState({ total: 60, assigned: 0, remaining: 60 });
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (initialData) {
      setFormData(initialData);
    }
    loadStats();
  }, [initialData]);

  const loadStats = async () => {
    const s = await getShareholderStats();
    setStats(s);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (name, value) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const shares = parseInt(formData.shares_assigned);
      
      // Calculate remaining excluding current user's existing shares if editing
      let effectiveRemaining = stats.remaining;
      if (initialData) {
        effectiveRemaining += parseInt(initialData.shares_assigned || 0);
      }

      if (shares > effectiveRemaining) {
        throw new Error(`Cannot assign ${shares} shares. Only ${effectiveRemaining} available.`);
      }

      if (initialData) {
        // For updates, you would need an updateShareholder function
        // For now, we'll just show a message
        toast({ 
          title: "Info", 
          description: "Update functionality needs to be implemented." 
        });
      } else {
        const { data, error } = await saveShareholderRegistration(formData);
        if (error) throw error;
        toast({ title: "Success", description: "Shareholder created successfully." });
      }
      onSuccess();
    } catch (error) {
      toast({ 
        title: "Error", 
        description: error.message, 
        variant: "destructive" 
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="grid gap-2">
        <Label htmlFor="name">Full Name <span className="text-red-500">*</span></Label>
        <Input 
          id="name" 
          name="name" 
          value={formData.name} 
          onChange={handleChange} 
          required 
          placeholder="e.g. John Doe"
        />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="grid gap-2">
          <Label htmlFor="email">Email <span className="text-red-500">*</span></Label>
          <Input 
            id="email" 
            name="email" 
            type="email"
            value={formData.email} 
            onChange={handleChange} 
            required 
            placeholder="john@example.com"
          />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="phone">Phone <span className="text-red-500">*</span></Label>
          <Input 
            id="phone" 
            name="phone" 
            value={formData.phone} 
            onChange={handleChange} 
            required 
            placeholder="+250..."
          />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="grid gap-2">
          <Label htmlFor="shares_assigned">
            Shares (Max: {initialData ? stats.remaining + parseInt(initialData.shares_assigned) : stats.remaining}) <span className="text-red-500">*</span>
          </Label>
          <Input 
            id="shares_assigned" 
            name="shares_assigned" 
            type="number"
            min="0"
            max="60"
            value={formData.shares_assigned} 
            onChange={handleChange} 
            required 
          />
        </div>
        <div className="grid gap-2">
          <Label htmlFor="payment_status">Payment Status</Label>
          <Select 
            value={formData.payment_status} 
            onValueChange={(val) => handleSelectChange('payment_status', val)}
          >
            <SelectTrigger>
              <SelectValue placeholder="Select status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Pay Later">Pay Later</SelectItem>
              <SelectItem value="VISA">VISA</SelectItem>
              <SelectItem value="Mobile Money">Mobile Money</SelectItem>
              <SelectItem value="Paid">Paid (Cash/Transfer)</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="flex justify-end gap-2 pt-4">
        <Button type="button" variant="outline" onClick={onCancel}>Cancel</Button>
        <Button type="submit" disabled={loading} className="bg-[#003D82]">
          {loading ? 'Saving...' : (initialData ? 'Update Shareholder' : 'Add Shareholder')}
        </Button>
      </div>
    </form>
  );
};

export default ShareholderForm;