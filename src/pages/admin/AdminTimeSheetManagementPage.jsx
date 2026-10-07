import React, { useState } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Calendar, Download, Eye, FileText } from 'lucide-react';

const AdminTimeSheetManagementPage = () => {
  const { entries, activities } = useTimeSheet();
  const [filterMonth, setFilterMonth] = useState(new Date().getMonth().toString());
  const [filterUser, setFilterUser] = useState('all');

  const currentYear = new Date().getFullYear();
  const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  // Get unique users from entries for the filter
  const users = [...new Set(entries.map(e => e.userId))].map(id => {
      const entry = entries.find(e => e.userId === id);
      return { id, name: entry?.userName || 'Unknown' };
  });

  const filteredEntries = entries.filter(e => {
      const d = new Date(e.date);
      const monthMatch = d.getMonth().toString() === filterMonth;
      const userMatch = filterUser === 'all' || e.userId === filterUser;
      return monthMatch && userMatch && d.getFullYear() === currentYear;
  });

  const exportCSV = () => {
    // Simple CSV Export Implementation
    const headers = ['Date', 'Employee', 'Activity', 'Hours', 'Notes'];
    const rows = filteredEntries.map(e => [
        e.date,
        e.userName,
        activities.find(a => a.id === e.activityId)?.name || e.activityName,
        e.hours,
        `"${e.notes || ''}"`
    ]);
    
    const csvContent = "data:text/csv;charset=utf-8," 
        + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "timesheet_export.csv");
    document.body.appendChild(link);
    link.click();
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Time Sheet Management</h1>
                <p className="text-gray-500">Review and manage employee time logs.</p>
            </div>
            <div className="flex items-center gap-2">
                <Button variant="outline" onClick={exportCSV} className="gap-2">
                    <Download className="w-4 h-4" /> Export
                </Button>
            </div>
        </div>

        {/* Filters */}
        <Card>
            <CardContent className="p-4 flex flex-col md:flex-row gap-4 items-end">
                <div className="space-y-2 flex-1 w-full">
                    <label className="text-sm font-medium">Filter by Employee</label>
                    <Select value={filterUser} onValueChange={setFilterUser}>
                        <SelectTrigger><SelectValue placeholder="All Employees" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Employees</SelectItem>
                            {users.map(u => <SelectItem key={u.id} value={u.id}>{u.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>
                <div className="space-y-2 flex-1 w-full">
                    <label className="text-sm font-medium">Filter by Month</label>
                    <Select value={filterMonth} onValueChange={setFilterMonth}>
                        <SelectTrigger><SelectValue placeholder="Select Month" /></SelectTrigger>
                        <SelectContent>
                            {MONTHS.map((m, idx) => <SelectItem key={idx} value={idx.toString()}>{m} {currentYear}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        {/* Data Table */}
        <Card className="border-t-4 border-t-[#D4AF37]">
            <CardHeader>
                <CardTitle className="flex justify-between items-center text-lg">
                    <span>Logged Entries</span>
                    <span className="text-sm font-normal bg-blue-50 text-[#003D82] px-3 py-1 rounded-full">
                        Total Hours: {filteredEntries.reduce((acc, cur) => acc + Number(cur.hours), 0).toFixed(1)}
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Employee</TableHead>
                                <TableHead>Activity</TableHead>
                                <TableHead>Hours</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredEntries.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                                        No entries found matching filters.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                filteredEntries.map((entry) => (
                                    <TableRow key={entry.id}>
                                        <TableCell className="font-medium">{entry.date}</TableCell>
                                        <TableCell>{entry.userName}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <span className="w-2 h-2 rounded-full bg-[#D4AF37]"></span>
                                                {activities.find(a => a.id === entry.activityId)?.name || entry.activityName}
                                            </div>
                                        </TableCell>
                                        <TableCell className="font-bold">{Number(entry.hours).toFixed(1)}</TableCell>
                                        <TableCell><span className="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Approved</span></TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm"><Eye className="w-4 h-4 text-gray-500" /></Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
  );
};

export default AdminTimeSheetManagementPage;