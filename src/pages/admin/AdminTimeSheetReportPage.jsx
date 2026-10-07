import React, { useState } from 'react';
import { generateTimesheetReport } from '@/services/timesheetReportService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Download, Filter, Loader2 } from 'lucide-react';
import { format } from 'date-fns';

const AdminTimeSheetReportPage = () => {
  const [filters, setFilters] = useState({
    startDate: '',
    endDate: '',
    employee_id: 'all'
  });
  const [reportData, setReportData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [generated, setGenerated] = useState(false);

  // Mock employee list - in real app fetch from userService
  const employees = [
    { id: 'all', name: 'All Employees' },
    { id: 'usr-1', name: 'John Doe' }, // Example IDs matching mocks if possible
    // ...
  ];

  const handleGenerate = async () => {
    setLoading(true);
    const data = await generateTimesheetReport(filters);
    setReportData(data);
    setGenerated(true);
    setLoading(false);
  };

  const handleExport = () => {
    // Basic CSV export logic
    const headers = ["Date", "Employee", "Activity", "Hours Worked", "Expected", "Difference", "Notes"];
    const rows = reportData.map(r => [
      r.date, 
      r.employee_name, 
      r.activity_name || 'Activity', 
      r.hours, 
      r.expected_hours, 
      r.difference, 
      `"${r.notes || ''}"`
    ]);
    
    const csvContent = "data:text/csv;charset=utf-8," 
      + headers.join(",") + "\n" 
      + rows.map(e => e.join(",")).join("\n");
      
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `timesheet_report_${new Date().toISOString()}.csv`);
    document.body.appendChild(link);
    link.click();
  };

  // Summary Calcs
  const totalWorked = reportData.reduce((sum, r) => sum + parseFloat(r.hours), 0);
  const totalExpected = reportData.reduce((sum, r) => sum + parseFloat(r.expected_hours), 0);
  const totalDiff = totalWorked - totalExpected;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Time Sheet Reports</h1>
        <p className="text-gray-500">Generate comprehensive time logs and variance reports.</p>
      </div>

      {/* Filters */}
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
          {/* Summary Cards */}
          <div className="grid md:grid-cols-4 gap-4">
            <Card className="bg-blue-50 border-blue-100">
              <CardContent className="p-4">
                <p className="text-sm text-blue-600 font-semibold uppercase">Total Hours Worked</p>
                <p className="text-2xl font-bold text-[#003D82]">{totalWorked.toFixed(2)}</p>
              </CardContent>
            </Card>
            <Card className="bg-gray-50 border-gray-100">
              <CardContent className="p-4">
                <p className="text-sm text-gray-600 font-semibold uppercase">Expected Hours</p>
                <p className="text-2xl font-bold text-gray-800">{totalExpected.toFixed(2)}</p>
              </CardContent>
            </Card>
            <Card className={totalDiff >= 0 ? "bg-green-50 border-green-100" : "bg-red-50 border-red-100"}>
              <CardContent className="p-4">
                <p className={`text-sm font-semibold uppercase ${totalDiff >= 0 ? "text-green-600" : "text-red-600"}`}>Difference</p>
                <p className={`text-2xl font-bold ${totalDiff >= 0 ? "text-green-800" : "text-red-800"}`}>
                  {totalDiff > 0 ? "+" : ""}{totalDiff.toFixed(2)}
                </p>
              </CardContent>
            </Card>
            <Card className="flex items-center justify-center">
               <Button variant="outline" onClick={handleExport} className="w-full h-full border-dashed border-2">
                 <Download className="w-4 h-4 mr-2" /> Export CSV
               </Button>
            </Card>
          </div>

          {/* Table */}
          <Card>
            <CardHeader><CardTitle>Detailed Logs</CardTitle></CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Employee</TableHead>
                    <TableHead>Activity</TableHead>
                    <TableHead>Hours</TableHead>
                    <TableHead>Expected</TableHead>
                    <TableHead>Variance</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {reportData.length === 0 ? (
                    <TableRow><TableCell colSpan={7} className="text-center py-8 text-gray-500">No records found for selected criteria.</TableCell></TableRow>
                  ) : (
                    reportData.map((row, i) => (
                      <TableRow key={i}>
                        <TableCell>{format(new Date(row.date), 'yyyy-MM-dd')}</TableCell>
                        <TableCell>{row.employee_name}</TableCell>
                        <TableCell>{row.activity_name || row.name || 'Activity'}</TableCell>
                        <TableCell className="font-bold">{parseFloat(row.hours).toFixed(2)}</TableCell>
                        <TableCell className="text-gray-500">{row.expected_hours.toFixed(2)}</TableCell>
                        <TableCell className={row.difference < 0 ? "text-red-500 font-medium" : "text-green-600 font-medium"}>
                          {row.difference > 0 ? "+" : ""}{row.difference.toFixed(2)}
                        </TableCell>
                        <TableCell className="text-xs text-gray-500 max-w-[200px] truncate">{row.notes}</TableCell>
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

export default AdminTimeSheetReportPage;