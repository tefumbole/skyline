import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Loader2, UserPlus } from 'lucide-react';
import { createUser } from '@/services/userService';
import { useToast } from '@/components/ui/use-toast';

const QuickAssigneeDialog = ({ open, onOpenChange, onCreated }) => {
  const { toast } = useToast();
  const [busy, setBusy] = useState(false);
  const [form, setForm] = useState({
    full_name: '',
    phone: '',
  });

  const resetForm = () => {
    setForm({ full_name: '', phone: '' });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.full_name.trim() || !form.phone.trim()) {
      toast({ title: 'Required fields', description: 'Name and WhatsApp phone are required.', variant: 'destructive' });
      return;
    }

    setBusy(true);
    try {
      const created = await createUser({
        full_name: form.full_name.trim(),
        phone: form.phone.trim(),
        asTaskGuest: true,
      });

      const assignee = {
        id: created.id,
        name: created.full_name || created.name || form.full_name,
        full_name: created.full_name || created.name || form.full_name,
        email: created.email || '',
        phone: created.phone || form.phone,
        role: created.role || 'task_assignee',
        type: 'customer',
      };

      toast({
        title: created.existing ? 'Person found' : 'Guest account created',
        description: created.existing
          ? `${assignee.name} already has an account and will receive the task link on WhatsApp.`
          : `${assignee.name} gets a guest login — username: ${created.username || assignee.phone}, password: system.`,
      });

      onCreated?.(assignee);
      resetForm();
      onOpenChange(false);
    } catch (err) {
      toast({ title: 'Could not add person', description: err.message, variant: 'destructive' });
    } finally {
      setBusy(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-[#003D82]">
            <UserPlus className="w-5 h-5" /> Add New Person
          </DialogTitle>
          <DialogDescription>
            Enter name and phone only. A <strong>guest account</strong> is created automatically — username is their
            phone number and the temporary password is <strong>system</strong>. They’ll be asked to set their own
            username, password, email and address on first login.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label>Full Name *</Label>
            <Input
              required
              value={form.full_name}
              onChange={(e) => setForm({ ...form, full_name: e.target.value })}
              placeholder="John Doe"
            />
          </div>
          <div>
            <Label>WhatsApp Phone *</Label>
            <Input
              required
              value={form.phone}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
              placeholder="+250..."
            />
            <p className="text-xs text-gray-500 mt-1">
              No email or password needed now. They log in with this phone number and the temporary
              password <strong>system</strong>, then set their own details.
            </p>
          </div>
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
            <Button type="submit" disabled={busy} className="bg-[#003D82] hover:bg-[#002a5a]">
              {busy ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Add & Assign'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default QuickAssigneeDialog;
