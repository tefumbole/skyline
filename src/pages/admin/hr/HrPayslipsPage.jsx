import React, { useEffect, useState } from 'react';
import { Download, MessageCircle, Printer } from 'lucide-react';
import { hrService, formatFcfa } from '@/services/hrService';
import { generateHrPayslipPdf, PAYSLIP_FORMATS } from '@/utils/hrPayslipPdf';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';

export default function HrPayslipsPage() {
  const { toast } = useToast();
  const [rows, setRows] = useState([]);
  const [printOpen, setPrintOpen] = useState(false);
  const [printItemId, setPrintItemId] = useState(null);
  const [printFormat, setPrintFormat] = useState('a4');

  const load = async () => {
    const res = await hrService.listPayslips();
    setRows(res.data || []);
  };

  useEffect(() => { load(); }, []);

  const openPrint = (itemId) => {
    setPrintItemId(itemId);
    setPrintFormat('a4');
    setPrintOpen(true);
  };

  const download = async () => {
    try {
      await hrService.generatePayslip(printItemId);
      const detail = await hrService.getPayslipDetail(printItemId);
      await generateHrPayslipPdf(detail.data, printFormat);
      toast({ title: 'Downloaded', description: `Payslip saved as ${printFormat.toUpperCase()} PDF.` });
      setPrintOpen(false);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const sendWa = async (itemId) => {
    try {
      const res = await hrService.sendPayslipWhatsApp(itemId);
      toast({ title: res.sent ? 'Sent' : 'Not sent', description: res.sent ? 'WhatsApp message sent.' : 'Could not send — check phone/Wasender.' });
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Payslips</h1>
        <p className="text-gray-500">Print A4, A5, or thermal receipts with QR code and barcode verification.</p>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Staff</TableHead>
                <TableHead>Payroll</TableHead>
                <TableHead>Net pay</TableHead>
                <TableHead>Code</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((r) => (
                <TableRow key={r.id}>
                  <TableCell>{r.first_name} {r.last_name}</TableCell>
                  <TableCell>{r.payroll_title}</TableCell>
                  <TableCell>{formatFcfa(r.net_amount)}</TableCell>
                  <TableCell className="font-mono text-xs">{r.verification_code}</TableCell>
                  <TableCell>{r.payment_status}</TableCell>
                  <TableCell className="text-right space-x-1">
                    <Button size="sm" variant="outline" onClick={() => openPrint(r.payroll_item_id)} title="Print / Download">
                      <Printer className="w-4 h-4" />
                    </Button>
                    <Button size="sm" variant="ghost" onClick={() => sendWa(r.payroll_item_id)}><MessageCircle className="w-4 h-4" /></Button>
                  </TableCell>
                </TableRow>
              ))}
              {!rows.length && <TableRow><TableCell colSpan={6} className="text-center py-8 text-gray-500">Generate payslips from a payroll run first.</TableCell></TableRow>}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={printOpen} onOpenChange={setPrintOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Choose print format</DialogTitle></DialogHeader>
          <div className="grid gap-2 py-2">
            {PAYSLIP_FORMATS.map((f) => (
              <button
                key={f.id}
                type="button"
                onClick={() => setPrintFormat(f.id)}
                className={`text-left px-4 py-3 rounded-lg border-2 transition-all ${
                  printFormat === f.id
                    ? 'border-[#003D82] bg-blue-50 font-semibold text-[#003D82]'
                    : 'border-slate-200 hover:border-slate-300'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setPrintOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={download}><Download className="w-4 h-4 mr-1" /> Download PDF</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
