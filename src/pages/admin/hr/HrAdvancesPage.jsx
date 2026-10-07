import React, { useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import { hrService, formatFcfa } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';

export default function HrAdvancesPage() {
  const { toast } = useToast();
  const [advances, setAdvances] = useState([]);
  const [staff, setStaff] = useState([]);
  const [jobs, setJobs] = useState([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ staff_profile_id: '', job_id: '', amount: '', paid_date: '', reason: '' });

  const load = async () => {
    const [a, s, j] = await Promise.all([
      hrService.listAdvances(),
      hrService.listStaff({ status: 'active' }),
      hrService.listJobs(),
    ]);
    setAdvances(a.data || []);
    setStaff(s.data || []);
    setJobs(j.data || []);
  };

  useEffect(() => { load(); }, []);

  const save = async () => {
    try {
      await hrService.createAdvance({ ...form, amount: Number(form.amount), job_id: form.job_id || null });
      toast({ title: 'Recorded', description: 'Advance payment saved.' });
      setOpen(false);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Advance / Partial Payments</h1>
          <p className="text-gray-500">Record advances deducted automatically on final payslip.</p>
        </div>
        <Button onClick={() => setOpen(true)} className="bg-[#003D82]"><Plus className="w-4 h-4 mr-2" /> Record advance</Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Staff</TableHead>
                <TableHead>Job</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Balance</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {advances.map((a) => (
                <TableRow key={a.id}>
                  <TableCell>{a.first_name} {a.last_name} ({a.staff_code})</TableCell>
                  <TableCell>{a.job_name || '—'}</TableCell>
                  <TableCell>{formatFcfa(a.amount)}</TableCell>
                  <TableCell>{formatFcfa(a.balance_remaining)}</TableCell>
                  <TableCell>{a.paid_date}</TableCell>
                  <TableCell className="capitalize">{a.status}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Record advance payment</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div>
              <Label>Staff</Label>
              <Select value={form.staff_profile_id} onValueChange={(v) => setForm({ ...form, staff_profile_id: v })}>
                <SelectTrigger><SelectValue placeholder="Select staff" /></SelectTrigger>
                <SelectContent>
                  {staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.first_name} {s.last_name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Job (optional)</Label>
              <Select value={form.job_id || 'none'} onValueChange={(v) => setForm({ ...form, job_id: v === 'none' ? '' : v })}>
                <SelectTrigger><SelectValue placeholder="General / no job" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  {jobs.map((j) => <SelectItem key={j.id} value={j.id}>{j.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div><Label>Amount</Label><Input type="number" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} /></div>
              <div><Label>Date paid</Label><Input type="date" value={form.paid_date} onChange={(e) => setForm({ ...form, paid_date: e.target.value })} /></div>
            </div>
            <div><Label>Reason</Label><Textarea value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} /></div>
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
