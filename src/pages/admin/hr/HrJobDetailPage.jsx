import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeft, Plus, RefreshCw, FileSpreadsheet } from 'lucide-react';
import { hrService, formatFcfa } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';

export default function HrJobDetailPage() {
  const { id } = useParams();
  const { toast } = useToast();
  const [job, setJob] = useState(null);
  const [allStaff, setAllStaff] = useState([]);
  const [open, setOpen] = useState(false);
  const [assignForm, setAssignForm] = useState({
    staff_profile_id: '', daily_rate: '', days_worked: 1, day_status: 'full', partial_fraction: 1,
  });

  const load = async () => {
    const [j, s] = await Promise.all([hrService.getJob(id), hrService.listStaff({ status: 'active' })]);
    setJob(j.data);
    setAllStaff(s.data || []);
  };

  useEffect(() => { load(); }, [id]);

  const assignStaff = async () => {
    try {
      const staff = allStaff.find((x) => x.id === assignForm.staff_profile_id);
      await hrService.assignJobStaff(id, {
        ...assignForm,
        daily_rate: assignForm.daily_rate || staff?.daily_rate,
        days_worked: Number(assignForm.days_worked),
        partial_fraction: Number(assignForm.partial_fraction),
      });
      toast({ title: 'Assigned', description: 'Staff added to job.' });
      setOpen(false);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const updateRow = async (rowId, patch) => {
    await hrService.updateJobStaff(id, rowId, patch);
    load();
  };

  const syncTimesheet = async () => {
    try {
      const res = await hrService.syncJobTimesheet(id);
      toast({ title: 'Synced', description: `${res.updated || 0} staff updated from timesheet.` });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const generatePayroll = async () => {
    try {
      const res = await hrService.createJobPayroll(id);
      toast({ title: 'Payroll created', description: 'Draft payroll generated.' });
      window.location.href = `/admin/hr/payroll/${res.data.id}`;
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  if (!job) return null;
  const staff = job.staff || [];

  return (
    <div className="space-y-6">
      <Button asChild variant="ghost" size="sm"><Link to="/admin/hr/jobs"><ArrowLeft className="w-4 h-4 mr-1" /> Back to jobs</Link></Button>

      <div className="flex flex-wrap justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">{job.name}</h1>
          <p className="text-gray-500">{job.client_name} · {job.location} · {job.start_date} → {job.end_date}</p>
        </div>
        <div className="flex gap-2 flex-wrap">
          <Button variant="outline" onClick={syncTimesheet}><RefreshCw className="w-4 h-4 mr-1" /> Sync timesheet</Button>
          <Button variant="outline" onClick={() => setOpen(true)}><Plus className="w-4 h-4 mr-1" /> Add staff</Button>
          <Button className="bg-[#003D82]" onClick={generatePayroll}><FileSpreadsheet className="w-4 h-4 mr-1" /> Generate payroll</Button>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle>Assigned Staff</CardTitle></CardHeader>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Staff</TableHead>
                <TableHead>Daily rate</TableHead>
                <TableHead>Days worked</TableHead>
                <TableHead>Day status</TableHead>
                <TableHead>Est. basic pay</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {staff.map((row) => {
                const days = row.day_status === 'partial'
                  ? Number(row.days_worked) * Number(row.partial_fraction || 1)
                  : Number(row.days_worked);
                const basic = days * Number(row.daily_rate);
                return (
                  <TableRow key={row.id}>
                    <TableCell>{row.first_name} {row.last_name} <span className="text-xs text-gray-400">({row.staff_code})</span></TableCell>
                    <TableCell>
                      <Input type="number" className="w-28 h-8" defaultValue={row.daily_rate}
                        onBlur={(e) => updateRow(row.id, { daily_rate: e.target.value })} />
                    </TableCell>
                    <TableCell>
                      <Input type="number" className="w-20 h-8" defaultValue={row.days_worked}
                        onBlur={(e) => updateRow(row.id, { days_worked: e.target.value })} />
                    </TableCell>
                    <TableCell>
                      <Select defaultValue={row.day_status} onValueChange={(v) => updateRow(row.id, { day_status: v })}>
                        <SelectTrigger className="w-28 h-8"><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="full">Full day</SelectItem>
                          <SelectItem value="partial">Partial</SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell>{formatFcfa(basic)}</TableCell>
                  </TableRow>
                );
              })}
              {!staff.length && <TableRow><TableCell colSpan={5} className="text-center py-8 text-gray-500">No staff assigned yet.</TableCell></TableRow>}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Assign staff to job</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div>
              <Label>Staff member</Label>
              <Select value={assignForm.staff_profile_id} onValueChange={(v) => {
                const s = allStaff.find((x) => x.id === v);
                setAssignForm({ ...assignForm, staff_profile_id: v, daily_rate: s?.daily_rate || '' });
              }}>
                <SelectTrigger><SelectValue placeholder="Select staff" /></SelectTrigger>
                <SelectContent>
                  {allStaff.map((s) => <SelectItem key={s.id} value={s.id}>{s.first_name} {s.last_name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div><Label>Daily rate</Label><Input type="number" value={assignForm.daily_rate} onChange={(e) => setAssignForm({ ...assignForm, daily_rate: e.target.value })} /></div>
              <div><Label>Days worked</Label><Input type="number" value={assignForm.days_worked} onChange={(e) => setAssignForm({ ...assignForm, days_worked: e.target.value })} /></div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={assignStaff}>Assign</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
