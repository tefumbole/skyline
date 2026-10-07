import React, { useEffect, useState } from 'react';
import { Plus, Edit2, Loader2, UserPlus, Search } from 'lucide-react';
import { hrService, formatFcfa } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';

const emptyForm = {
  first_name: '', last_name: '', email: '', phone: '', category_id: '', position: '',
  payment_type: 'daily', daily_rate: '', monthly_salary: '', user_id: '', hire_date: '',
  staff_code: '', role: '',
};

export default function HrStaffPage() {
  const { toast } = useToast();
  const [staff, setStaff] = useState([]);
  const [categories, setCategories] = useState([]);
  const [positionRates, setPositionRates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [editId, setEditId] = useState(null);
  const [userSearch, setUserSearch] = useState('');
  const [userResults, setUserResults] = useState([]);
  const [search, setSearch] = useState('');

  const load = async () => {
    setLoading(true);
    try {
      const [s, c, p] = await Promise.all([
        hrService.listStaff({ q: search || undefined }),
        hrService.listCategories(),
        hrService.listPositionRates(),
      ]);
      setStaff(s.data || []);
      setCategories(c.data || []);
      setPositionRates(p.data || []);
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, []);

  const openCreate = async () => {
    setEditId(null);
    try {
      const next = await hrService.getNextStaffCode();
      setForm({ ...emptyForm, staff_code: next.staff_code || '' });
    } catch {
      setForm(emptyForm);
    }
    setOpen(true);
  };

  const openEdit = (row) => {
    setEditId(row.id);
    setForm({
      first_name: row.first_name,
      last_name: row.last_name,
      email: row.email || '',
      phone: row.phone || '',
      category_id: row.category_id,
      position: row.position || '',
      payment_type: row.payment_type || 'daily',
      daily_rate: row.daily_rate ?? '',
      monthly_salary: row.monthly_salary ?? '',
      user_id: row.user_id || '',
      hire_date: row.hire_date || '',
      staff_code: row.staff_code || '',
      role: row.role || 'Staff',
    });
    setOpen(true);
  };

  const onPositionChange = (position) => {
    const rate = positionRates.find((r) => r.position === position);
    setForm((f) => ({
      ...f,
      position,
      daily_rate: rate ? rate.daily_rate : f.daily_rate,
    }));
  };

  const searchUsers = async (q) => {
    setUserSearch(q);
    if (q.length < 2) return setUserResults([]);
    const res = await hrService.searchUsers(q);
    setUserResults(res.data || []);
  };

  const pickUser = (u) => {
    if (u.is_staff) {
      toast({ title: 'Already staff', description: `${u.name} is already on staff (${u.staff_code}).`, variant: 'destructive' });
      return;
    }
    const parts = String(u.name || '').split(' ');
    setForm((f) => ({
      ...f,
      user_id: u.id,
      first_name: parts[0] || '',
      last_name: parts.slice(1).join(' '),
      email: u.email || '',
      phone: u.phone || '',
      role: u.role || 'Staff',
      hire_date: u.suggested_hire_date || new Date().toISOString().slice(0, 10),
      staff_code: u.suggested_staff_code || f.staff_code,
    }));
    setUserResults([]);
    setUserSearch('');
    toast({ title: 'User selected', description: 'Role, employment date, and employee number filled. User will get Staff access on save.' });
  };

  const save = async () => {
    try {
      const body = {
        ...form,
        daily_rate: form.daily_rate !== '' ? Number(form.daily_rate) : null,
        monthly_salary: form.monthly_salary !== '' ? Number(form.monthly_salary) : null,
        user_id: form.user_id || null,
        staff_code: form.staff_code || undefined,
      };
      if (editId) await hrService.updateStaff(editId, body);
      else await hrService.createStaff(body);
      toast({
        title: editId ? 'Updated' : 'Created',
        description: form.user_id
          ? 'Staff saved. User promoted to Staff with My Tasks, Jobs, and Timesheet access.'
          : 'Staff profile saved.',
      });
      setOpen(false);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const typeBadge = (t) => {
    if (t === 'customer') return 'Customer';
    if (t === 'staff') return 'Staff';
    return 'User';
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex flex-wrap justify-between gap-4 items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Staff Management</h1>
          <p className="text-gray-500">Create staff profiles or convert customers and system users.</p>
        </div>
        <Button onClick={openCreate} className="bg-[#003D82]">
          <Plus className="w-4 h-4 mr-2" /> Add Staff
        </Button>
      </div>

      <div className="flex gap-2 max-w-md">
        <Input placeholder="Search staff..." value={search} onChange={(e) => setSearch(e.target.value)} />
        <Button variant="outline" onClick={load}>Search</Button>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="p-8 flex justify-center"><Loader2 className="animate-spin" /></div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Employee No.</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Position</TableHead>
                  <TableHead>Employment</TableHead>
                  <TableHead>Payment</TableHead>
                  <TableHead>Rate / Salary</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {staff.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell className="font-mono text-sm">{row.staff_code}</TableCell>
                    <TableCell>{row.first_name} {row.last_name}</TableCell>
                    <TableCell>{row.category_name}</TableCell>
                    <TableCell>{row.position || '—'}</TableCell>
                    <TableCell>{row.hire_date || '—'}</TableCell>
                    <TableCell className="capitalize">{row.payment_type}</TableCell>
                    <TableCell>
                      {row.payment_type === 'monthly'
                        ? formatFcfa(row.monthly_salary)
                        : `${formatFcfa(row.daily_rate)}/day`}
                    </TableCell>
                    <TableCell className="capitalize">{row.status}</TableCell>
                    <TableCell className="text-right">
                      <Button size="sm" variant="ghost" onClick={() => openEdit(row)}>
                        <Edit2 className="w-4 h-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
                {!staff.length && (
                  <TableRow><TableCell colSpan={9} className="text-center text-gray-500 py-8">No staff yet.</TableCell></TableRow>
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
          <DialogHeader><DialogTitle>{editId ? 'Edit Staff' : 'Add Staff'}</DialogTitle></DialogHeader>
          {!editId && (
            <div className="space-y-2 border rounded-lg p-3 bg-slate-50">
              <Label className="flex items-center gap-2"><UserPlus className="w-4 h-4" /> Convert customer or system user</Label>
              <div className="relative">
                <Search className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                <Input className="pl-9" placeholder="Search customers, staff, or any user..." value={userSearch} onChange={(e) => searchUsers(e.target.value)} />
              </div>
              {userResults.map((u) => (
                <button key={u.id} type="button" onClick={() => pickUser(u)} disabled={u.is_staff}
                  className="w-full text-left px-3 py-2 rounded hover:bg-white border text-sm disabled:opacity-50">
                  <span className="font-medium">{u.name}</span>
                  <span className="text-xs ml-2 px-1.5 py-0.5 rounded bg-slate-200">{typeBadge(u.user_type)}</span>
                  {u.is_staff ? ` · ${u.staff_code}` : ''} — {u.email || u.phone}
                </button>
              ))}
            </div>
          )}
          <div className="grid grid-cols-2 gap-3">
            <div><Label>Employee number</Label><Input value={form.staff_code} readOnly className="bg-slate-50 font-mono" /></div>
            <div><Label>Role</Label><Input value={form.role || 'Staff'} readOnly className="bg-slate-50" /></div>
            <div><Label>First name</Label><Input value={form.first_name} onChange={(e) => setForm({ ...form, first_name: e.target.value })} /></div>
            <div><Label>Last name</Label><Input value={form.last_name} onChange={(e) => setForm({ ...form, last_name: e.target.value })} /></div>
            <div><Label>Email</Label><Input value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></div>
            <div><Label>Phone</Label><Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></div>
            <div className="col-span-2"><Label>Date of employment</Label><Input type="date" value={form.hire_date} onChange={(e) => setForm({ ...form, hire_date: e.target.value })} /></div>
            <div className="col-span-2">
              <Label>Category</Label>
              <Select value={form.category_id} onValueChange={(v) => setForm({ ...form, category_id: v })}>
                <SelectTrigger><SelectValue placeholder="Select category" /></SelectTrigger>
                <SelectContent>
                  {categories.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="col-span-2">
              <Label>Position</Label>
              <Select value={form.position} onValueChange={onPositionChange}>
                <SelectTrigger><SelectValue placeholder="Select position" /></SelectTrigger>
                <SelectContent>
                  {positionRates.map((r) => <SelectItem key={r.id} value={r.position}>{r.position}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Payment type</Label>
              <Select value={form.payment_type} onValueChange={(v) => setForm({ ...form, payment_type: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="daily">Daily</SelectItem>
                  <SelectItem value="monthly">Monthly</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              {form.payment_type === 'monthly' ? (
                <><Label>Monthly salary</Label><Input type="number" value={form.monthly_salary} onChange={(e) => setForm({ ...form, monthly_salary: e.target.value })} /></>
              ) : (
                <><Label>Daily rate (FCFA)</Label><Input type="number" value={form.daily_rate} onChange={(e) => setForm({ ...form, daily_rate: e.target.value })} /></>
              )}
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={save}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
