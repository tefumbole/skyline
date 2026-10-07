import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { CheckCircle, Send, XCircle, Wallet } from 'lucide-react';
import { hrService, formatFcfa } from '@/services/hrService';
import { generateHrPayslipPdf } from '@/utils/hrPayslipPdf';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';

export default function HrPayrollDetailPage() {
  const { id } = useParams();
  const { toast } = useToast();
  const [run, setRun] = useState(null);
  const [allowanceTypes, setAllowanceTypes] = useState([]);
  const [deductionTypes, setDeductionTypes] = useState([]);
  const [editItem, setEditItem] = useState(null);

  const load = async () => {
    const [r, a, d] = await Promise.all([
      hrService.getPayrollRun(id),
      hrService.listAllowanceTypes(),
      hrService.listDeductionTypes(),
    ]);
    setRun(r.data);
    setAllowanceTypes(a.data || []);
    setDeductionTypes(d.data || []);
  };

  useEffect(() => { load(); }, [id]);

  const workflow = async (action) => {
    try {
      const map = {
        review: hrService.submitPayrollReview,
        approve: hrService.approvePayroll,
        finance: hrService.forwardPayrollFinance,
        reject: hrService.rejectPayroll,
      };
      await map[action](id);
      toast({ title: 'Updated', description: `Payroll ${action} completed.` });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const saveItem = async () => {
    if (!editItem) return;
    try {
      await hrService.updatePayrollItem(id, editItem.id, {
        allowances: editItem.allowances,
        deductions: editItem.deductions,
        days_worked: editItem.days_worked,
        daily_rate: editItem.daily_rate,
      });
      toast({ title: 'Saved' });
      setEditItem(null);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const downloadPayslip = async (itemId) => {
    try {
      await hrService.generatePayslip(itemId);
      const detail = await hrService.getPayslipDetail(itemId);
      await generateHrPayslipPdf(detail.data);
      toast({ title: 'Downloaded', description: 'Payslip PDF generated.' });
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  if (!run) return null;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">{run.title}</h1>
          <p className="text-gray-500 capitalize">{run.run_type} payroll · Status: {run.status}</p>
        </div>
        <div className="flex gap-2 flex-wrap">
          {run.status === 'draft' && <Button onClick={() => workflow('review')}><Send className="w-4 h-4 mr-1" /> Submit for review</Button>}
          {run.status === 'review' && <Button className="bg-green-700" onClick={() => workflow('approve')}><CheckCircle className="w-4 h-4 mr-1" /> Approve</Button>}
          {run.status === 'approved' && <Button className="bg-[#003D82]" onClick={() => workflow('finance')}><Wallet className="w-4 h-4 mr-1" /> Forward to Finance</Button>}
          {!['paid', 'rejected'].includes(run.status) && (
            <Button variant="destructive" onClick={() => workflow('reject')}><XCircle className="w-4 h-4 mr-1" /> Reject</Button>
          )}
        </div>
      </div>

      <div className="grid md:grid-cols-3 gap-4">
        <Card><CardContent className="pt-6"><div className="text-sm text-gray-500">Gross total</div><div className="text-2xl font-bold">{formatFcfa(run.total_gross)}</div></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-sm text-gray-500">Net total</div><div className="text-2xl font-bold text-[#003D82]">{formatFcfa(run.total_net)}</div></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-sm text-gray-500">Staff count</div><div className="text-2xl font-bold">{run.items?.length || 0}</div></CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Payroll items</CardTitle></CardHeader>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Staff</TableHead>
                <TableHead>Basic</TableHead>
                <TableHead>Allowances</TableHead>
                <TableHead>Deductions</TableHead>
                <TableHead>Advances</TableHead>
                <TableHead>Net</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {(run.items || []).map((item) => (
                <TableRow key={item.id}>
                  <TableCell>{item.first_name} {item.last_name}</TableCell>
                  <TableCell>{formatFcfa(item.basic_amount)}</TableCell>
                  <TableCell>{formatFcfa(item.total_allowances)}</TableCell>
                  <TableCell>{formatFcfa(item.total_deductions)}</TableCell>
                  <TableCell>{formatFcfa(item.total_advances)}</TableCell>
                  <TableCell className="font-semibold">{formatFcfa(item.net_amount)}</TableCell>
                  <TableCell><Badge variant="outline">{item.payment_status}</Badge></TableCell>
                  <TableCell className="text-right space-x-1">
                    <Button size="sm" variant="outline" onClick={() => setEditItem({ ...item, allowances: item.allowances || [], deductions: item.deductions || [] })}>Edit</Button>
                    <Button size="sm" variant="ghost" onClick={() => downloadPayslip(item.id)}>PDF</Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {editItem && (
        <Card className="border-[#003D82]">
          <CardHeader><CardTitle>Edit — {editItem.first_name} {editItem.last_name}</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div><Label>Daily rate</Label><Input type="number" value={editItem.daily_rate || ''} onChange={(e) => setEditItem({ ...editItem, daily_rate: e.target.value })} /></div>
              <div><Label>Days worked</Label><Input type="number" value={editItem.days_worked || ''} onChange={(e) => setEditItem({ ...editItem, days_worked: e.target.value })} /></div>
            </div>
            <div>
              <Label>Add allowance</Label>
              <div className="flex gap-2 mt-1">
                <select className="border rounded px-2" onChange={(e) => {
                  const t = allowanceTypes.find((x) => x.id === e.target.value);
                  if (!t) return;
                  setEditItem({
                    ...editItem,
                    allowances: [...(editItem.allowances || []), { allowance_type_id: t.id, label: t.name, amount: t.default_amount }],
                  });
                }}>
                  <option value="">Select type...</option>
                  {allowanceTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              </div>
              {(editItem.allowances || []).map((a, i) => (
                <div key={i} className="flex gap-2 mt-2">
                  <Input value={a.label} readOnly className="flex-1" />
                  <Input type="number" value={a.amount} onChange={(e) => {
                    const allowances = [...editItem.allowances];
                    allowances[i].amount = e.target.value;
                    setEditItem({ ...editItem, allowances });
                  }} className="w-32" />
                </div>
              ))}
            </div>
            <div>
              <Label>Add deduction</Label>
              <select className="border rounded px-2 mt-1" onChange={(e) => {
                const t = deductionTypes.find((x) => x.id === e.target.value);
                if (!t) return;
                setEditItem({
                  ...editItem,
                  deductions: [...(editItem.deductions || []), { deduction_type_id: t.id, label: t.name, amount: t.default_amount }],
                });
              }}>
                <option value="">Select type...</option>
                {deductionTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
              {(editItem.deductions || []).map((d, i) => (
                <div key={i} className="flex gap-2 mt-2">
                  <Input value={d.label} readOnly className="flex-1" />
                  <Input type="number" value={d.amount} onChange={(e) => {
                    const deductions = [...editItem.deductions];
                    deductions[i].amount = e.target.value;
                    setEditItem({ ...editItem, deductions });
                  }} className="w-32" />
                </div>
              ))}
            </div>
            <div className="flex gap-2">
              <Button className="bg-[#003D82]" onClick={saveItem}>Save item</Button>
              <Button variant="outline" onClick={() => setEditItem(null)}>Cancel</Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
