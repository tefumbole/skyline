import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { hrService, formatFcfa } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';

export default function HrApprovalsPage() {
  const [runs, setRuns] = useState([]);

  useEffect(() => {
    hrService.listPayrollRuns().then((res) => setRuns(res.data || []));
  }, []);

  const pending = runs.filter((r) => ['draft', 'review', 'approved'].includes(r.status));

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Payroll Approvals</h1>
        <p className="text-gray-500">Review draft payroll, approve, and forward to Finance.</p>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Title</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Net total</TableHead>
                <TableHead className="text-right">Review</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {pending.map((r) => (
                <TableRow key={r.id}>
                  <TableCell>{r.title}</TableCell>
                  <TableCell className="capitalize">{r.run_type}</TableCell>
                  <TableCell><Badge>{r.status}</Badge></TableCell>
                  <TableCell>{formatFcfa(r.total_net)}</TableCell>
                  <TableCell className="text-right">
                    <Button asChild size="sm" className="bg-[#003D82]"><Link to={`/admin/hr/payroll/${r.id}`}>Open</Link></Button>
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
