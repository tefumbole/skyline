import React, { useEffect, useState } from 'react';
import { hrService, formatFcfa } from '@/services/hrService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function HrReportsPage() {
  const [data, setData] = useState(null);
  const [month, setMonth] = useState('');

  useEffect(() => {
    hrService.getReportsSummary(month || undefined).then((res) => setData(res.data));
  }, [month]);

  if (!data) return null;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Payroll Reports</h1>
        <p className="text-gray-500">Totals by month, job, engineers, unpaid payroll, allowances, deductions, and advances.</p>
      </div>

      <div className="max-w-xs">
        <Label>Filter month (YYYY-MM)</Label>
        <Input type="month" value={month} onChange={(e) => setMonth(e.target.value)} />
      </div>

      <div className="grid md:grid-cols-3 gap-4">
        <Card><CardContent className="pt-6"><div className="text-sm text-gray-500">Unpaid payroll</div><div className="text-2xl font-bold text-red-600">{formatFcfa(data.unpaidTotal)}</div></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-sm text-gray-500">Open advance balance</div><div className="text-2xl font-bold">{formatFcfa(data.advances?.open_balance)}</div></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-sm text-gray-500">Total advanced</div><div className="text-2xl font-bold">{formatFcfa(data.advances?.total_advanced)}</div></CardContent></Card>
      </div>

      <div className="grid md:grid-cols-2 gap-6">
        <Card>
          <CardHeader><CardTitle>Payroll by month</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {(data.byMonth || []).map((r) => (
              <div key={r.month} className="flex justify-between border-b py-2 text-sm">
                <span>{r.month}</span>
                <span className="font-semibold">{formatFcfa(r.total_net)}</span>
              </div>
            ))}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Payroll by job / event</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {(data.byJob || []).map((r, i) => (
              <div key={i} className="flex justify-between border-b py-2 text-sm">
                <span>{r.name}</span>
                <span className="font-semibold">{formatFcfa(r.total_net)}</span>
              </div>
            ))}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Engineers paid</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {(data.engineers || []).map((r, i) => (
              <div key={i} className="flex justify-between border-b py-2 text-sm">
                <span>{r.first_name} {r.last_name}</span>
                <span className="font-semibold">{formatFcfa(r.total_paid)}</span>
              </div>
            ))}
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Allowances & deductions</CardTitle></CardHeader>
          <CardContent>
            <div className="text-xs font-semibold text-gray-500 mb-2">ALLOWANCES</div>
            {(data.allowances || []).map((r, i) => (
              <div key={i} className="flex justify-between text-sm py-1"><span>{r.label}</span><span>{formatFcfa(r.total)}</span></div>
            ))}
            <div className="text-xs font-semibold text-gray-500 mt-4 mb-2">DEDUCTIONS</div>
            {(data.deductions || []).map((r, i) => (
              <div key={i} className="flex justify-between text-sm py-1"><span>{r.label}</span><span>{formatFcfa(r.total)}</span></div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
