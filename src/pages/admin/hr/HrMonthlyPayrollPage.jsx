import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { hrService, formatFcfa } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';

export default function HrMonthlyPayrollPage() {
  const { toast } = useToast();
  const [runs, setRuns] = useState([]);
  const [period, setPeriod] = useState({ period_start: '', period_end: '', title: '' });

  const load = async () => {
    const res = await hrService.listPayrollRuns({ run_type: 'monthly' });
    setRuns(res.data || []);
  };

  useEffect(() => { load(); }, []);

  const generate = async () => {
    try {
      const res = await hrService.createMonthlyPayroll(period);
      toast({ title: 'Created', description: 'Monthly payroll draft generated.' });
      window.location.href = `/admin/hr/payroll/${res.data.id}`;
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Monthly Payroll</h1>
        <p className="text-gray-500">Generate payroll for permanent staff with timesheet hours (40 hrs/week expected).</p>
      </div>

      <Card className="p-6">
        <div className="grid md:grid-cols-4 gap-4 items-end">
          <div><Label>Period start</Label><Input type="date" value={period.period_start} onChange={(e) => setPeriod({ ...period, period_start: e.target.value })} /></div>
          <div><Label>Period end</Label><Input type="date" value={period.period_end} onChange={(e) => setPeriod({ ...period, period_end: e.target.value })} /></div>
          <div><Label>Title (optional)</Label><Input value={period.title} onChange={(e) => setPeriod({ ...period, title: e.target.value })} placeholder="March 2026 Payroll" /></div>
          <Button className="bg-[#003D82]" onClick={generate}>Generate payroll</Button>
        </div>
      </Card>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Title</TableHead>
                <TableHead>Period</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Net total</TableHead>
                <TableHead className="text-right">Open</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {runs.map((r) => (
                <TableRow key={r.id}>
                  <TableCell>{r.title}</TableCell>
                  <TableCell>{r.period_start} → {r.period_end}</TableCell>
                  <TableCell className="capitalize">{r.status}</TableCell>
                  <TableCell>{formatFcfa(r.total_net)}</TableCell>
                  <TableCell className="text-right">
                    <Button asChild size="sm" variant="outline"><Link to={`/admin/hr/payroll/${r.id}`}>View</Link></Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
