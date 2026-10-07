import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Plus, Loader2, Briefcase } from 'lucide-react';
import { hrService } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';

export default function HrJobsPage() {
  const { toast } = useToast();
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name: '', client_name: '', location: '', description: '', start_date: '', end_date: '' });

  const load = async () => {
    setLoading(true);
    try {
      const res = await hrService.listJobs();
      setJobs(res.data || []);
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, []);

  const save = async () => {
    try {
      await hrService.createJob(form);
      toast({ title: 'Created', description: 'Job/event added.' });
      setOpen(false);
      setForm({ name: '', client_name: '', location: '', description: '', start_date: '', end_date: '' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Job / Event Payroll</h1>
          <p className="text-gray-500">Create jobs or events and assign staff for daily payroll.</p>
        </div>
        <Button onClick={() => setOpen(true)} className="bg-[#003D82]">
          <Plus className="w-4 h-4 mr-2" /> New Job / Event
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? <div className="p-8 flex justify-center"><Loader2 className="animate-spin" /></div> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Job / Event</TableHead>
                  <TableHead>Client</TableHead>
                  <TableHead>Dates</TableHead>
                  <TableHead>Staff</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {jobs.map((j) => (
                  <TableRow key={j.id}>
                    <TableCell className="font-medium">{j.name}</TableCell>
                    <TableCell>{j.client_name || '—'}</TableCell>
                    <TableCell>{j.start_date || '—'} → {j.end_date || '—'}</TableCell>
                    <TableCell>{j.staff_count || 0}</TableCell>
                    <TableCell className="capitalize">{j.status}</TableCell>
                    <TableCell className="text-right">
                      <Button asChild size="sm" variant="outline">
                        <Link to={`/admin/hr/jobs/${j.id}`}><Briefcase className="w-4 h-4 mr-1" /> Manage</Link>
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>New Job / Event</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div><Label>Name</Label><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Dr. Njume's Wedding" /></div>
            <div><Label>Client</Label><Input value={form.client_name} onChange={(e) => setForm({ ...form, client_name: e.target.value })} /></div>
            <div><Label>Location</Label><Input value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} /></div>
            <div className="grid grid-cols-2 gap-3">
              <div><Label>Start date</Label><Input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} /></div>
              <div><Label>End date</Label><Input type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} /></div>
            </div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={save}>Create</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
