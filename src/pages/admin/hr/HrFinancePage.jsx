import React, { useEffect, useState } from 'react';
import { hrService, formatFcfa } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';

export default function HrFinancePage() {
  const { toast } = useToast();
  const [rows, setRows] = useState([]);
  const [filter, setFilter] = useState('pending');

  const load = async () => {
    const res = await hrService.listFinance({ status: filter === 'all' ? undefined : filter });
    setRows(res.data || []);
  };

  useEffect(() => { load(); }, [filter]);

  const updateStatus = async (id, status) => {
    try {
      await hrService.updateFinancePayment(id, { status });
      toast({ title: 'Updated', description: `Marked as ${status}.` });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Finance Payment Status</h1>
          <p className="text-gray-500">Mark payroll items pending, approved, partially paid, paid, or rejected.</p>
        </div>
        <Select value={filter} onValueChange={setFilter}>
          <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="approved_for_payment">Approved</SelectItem>
            <SelectItem value="partially_paid">Partially paid</SelectItem>
            <SelectItem value="paid">Paid</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Staff</TableHead>
                <TableHead>Payroll</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((r) => (
                <TableRow key={r.id}>
                  <TableCell>{r.first_name} {r.last_name}</TableCell>
                  <TableCell>{r.payroll_title}</TableCell>
                  <TableCell>{formatFcfa(r.amount)}</TableCell>
                  <TableCell className="capitalize">{r.status.replace(/_/g, ' ')}</TableCell>
                  <TableCell className="text-right space-x-1">
                    <Button size="sm" variant="outline" onClick={() => updateStatus(r.id, 'approved_for_payment')}>Approve</Button>
                    <Button size="sm" className="bg-green-700" onClick={() => updateStatus(r.id, 'paid')}>Paid</Button>
                    <Button size="sm" variant="destructive" onClick={() => updateStatus(r.id, 'rejected')}>Reject</Button>
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
