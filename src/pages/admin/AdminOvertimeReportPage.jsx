import React, { useState } from 'react';
import { getOvertimeReport } from '@/services/overtimeService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Filter, Loader2, TrendingUp } from 'lucide-react';

const AdminOvertimeReportPage = () => {
  const [filters, setFilters] = useState({
    startDate: '',
    endDate: '',
    employee_id: 'all'
  });
  const [reportData, setReportData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [generated, setGenerated] = useState(false);

  // Mock
  const employees = [{ id: 'all', name: 'All Employees' }];

  const handleGenerate = async () => {
    setLoading(true);
    const data = await getOvertimeReport(filters);
    setReportData(data);
    setGenerated(true);
    setLoading(false);
  };

  const totalOvertime = reportData.reduce((sum, r) => sum + r.overtime_hours, 0);
  const employeesWithOvertime = new Set(reportData.filter(r => r.overtime_hours > 0).map(r => r.employee_id)).size;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Overtime Reports</h1>
        <p className="text-gray-500">Track extra hours worked beyond scheduled expectations.</p>
      </div>

      <Card>
        <CardContent className="p-6">
          <div className="grid md:grid-cols-4 gap-4 items-end">
            <div className="space-y-2">
              <Label>From Date</Label>
              <Input type="date" value={filters.startDate} onChange={e => setFilters({...filters, startDate: e.target.value})} />
            </div>
            <div className="space-y-2">
              <Label>To Date</Label>
              <Input type="date" value={filters.endDate} onChange={e => setFilters({...filters, endDate: e.target.value})} />
            </div>
            <div className="space-y-2">
              <Label>Employee</Label>
              <Select value={filters.employee_id} onValueChange={val => setFilters({...filters, employee_id: val})}>
                <SelectTrigger><SelectValue placeholder="All Employees" /></SelectTrigger>
                <SelectContent>
                  {employees.map(e => <SelectItem key={e.id} value={e.id}>{e.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <Button onClick={handleGenerate} className="bg-[#003D82]" disabled={loading}>
              {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Filter className="w-4 h-4 mr-2" />}
              Generate Report
            </Button>
          </div>
        </CardContent>
      </Card>

      {generated && (
          <>
            <div className="grid md:grid-cols-3 gap-6">
                <Card className="bg-[#003D82] text-white">
                    <CardContent className="p-6">
                        <p className="text-sm opacity-80 uppercase font-semibold">Total Overtime Hours</p>
                        <p className="text-3xl font-bold flex items-center gap-2">
                            {totalOvertime.toFixed(1)} <TrendingUp className="w-6 h-6 text-[#D4AF37]" />
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-6">
                        <p className="text-sm text-gray-500 uppercase font-semibold">Employees with Overtime</p>
                        <p className="text-3xl font-bold text-gray-800">{employeesWithOvertime}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-6">
                        <p className="text-sm text-gray-500 uppercase font-semibold">Avg. Overtime / Person</p>
                        <p className="text-3xl font-bold text-gray-800">
                             {employeesWithOvertime > 0 ? (totalOvertime / employeesWithOvertime).toFixed(1) : 0}h
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle>Weekly Overtime Breakdown</CardTitle></CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Week</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Expected</TableHead>
                                <TableHead>Worked</TableHead>
                                <TableHead>Overtime</TableHead>
                                <TableHead>Rate</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {reportData.length === 0 ? (
                                <TableRow><TableCell colSpan={6} className="text-center py-8">No overtime data found.</TableCell></TableRow>
                            ) : (
                                reportData.map((row, i) => (
                                    <TableRow key={i}>
                                        <TableCell>{row.week}</TableCell>
                                        <TableCell>{row.employee_name}</TableCell>
                                        <TableCell>{row.expected_hours}h</TableCell>
                                        <TableCell>{row.total_hours}h</TableCell>
                                        <TableCell className="font-bold text-[#D4AF37]">{row.overtime_hours.toFixed(2)}h</TableCell>
                                        <TableCell>x{row.overtime_pay_rate}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
          </>
      )}
    </div>
  );
};

export default AdminOvertimeReportPage;