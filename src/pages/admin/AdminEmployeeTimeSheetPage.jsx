import React, { useState } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { User, Clock, CalendarDays, BarChart2 } from 'lucide-react';

const AdminEmployeeTimeSheetPage = () => {
  const { entries, activities } = useTimeSheet();
  const [selectedUser, setSelectedUser] = useState('');
  
  // Get unique users
  const users = [...new Set(entries.map(e => e.userId))].map(id => {
      const entry = entries.find(e => e.userId === id);
      return { id, name: entry?.userName || 'Unknown' };
  });

  const userEntries = entries.filter(e => e.userId === selectedUser).sort((a,b) => new Date(b.date) - new Date(a.date));
  
  const totalHours = userEntries.reduce((acc, curr) => acc + Number(curr.hours), 0);
  const avgHours = userEntries.length ? (totalHours / userEntries.length).toFixed(1) : 0;

  // Activity Breakdown
  const activityBreakdown = userEntries.reduce((acc, curr) => {
      const name = activities.find(a => a.id === curr.activityId)?.name || curr.activityName || 'Unknown';
      acc[name] = (acc[name] || 0) + Number(curr.hours);
      return acc;
  }, {});

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
        <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Individual Employee Time Sheet</h1>
            <p className="text-gray-500">Detailed view of a single employee's logs.</p>
        </div>

        <Card className="bg-blue-50 border-blue-200">
            <CardContent className="p-6">
                <label className="block text-sm font-bold text-[#003D82] mb-2">Select Employee</label>
                <Select value={selectedUser} onValueChange={setSelectedUser}>
                    <SelectTrigger className="bg-white"><SelectValue placeholder="Choose an employee to view..." /></SelectTrigger>
                    <SelectContent>
                        {users.map(u => <SelectItem key={u.id} value={u.id}>{u.name}</SelectItem>)}
                    </SelectContent>
                </Select>
            </CardContent>
        </Card>

        {selectedUser ? (
            <div className="grid gap-6">
                <div className="grid md:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Hours Logged</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent><div className="text-2xl font-bold">{totalHours.toFixed(1)}</div></CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Avg. Daily Hours</CardTitle>
                            <BarChart2 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent><div className="text-2xl font-bold">{avgHours}</div></CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Days</CardTitle>
                            <CalendarDays className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent><div className="text-2xl font-bold">{new Set(userEntries.map(e => e.date)).size}</div></CardContent>
                    </Card>
                </div>

                <div className="grid md:grid-cols-3 gap-6">
                    <Card className="md:col-span-2">
                        <CardHeader><CardTitle>Time Log History</CardTitle></CardHeader>
                        <CardContent>
                             <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Activity</TableHead>
                                        <TableHead>Hours</TableHead>
                                        <TableHead>Notes</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {userEntries.map((e) => (
                                        <TableRow key={e.id}>
                                            <TableCell>{e.date}</TableCell>
                                            <TableCell>{activities.find(a => a.id === e.activityId)?.name || e.activityName}</TableCell>
                                            <TableCell className="font-bold">{Number(e.hours).toFixed(1)}</TableCell>
                                            <TableCell className="text-gray-500 text-sm max-w-[200px] truncate">{e.notes}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card className="md:col-span-1">
                        <CardHeader><CardTitle>Activity Distribution</CardTitle></CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {Object.entries(activityBreakdown).map(([name, hours]) => (
                                    <div key={name} className="space-y-1">
                                        <div className="flex justify-between text-sm">
                                            <span>{name}</span>
                                            <span className="font-semibold">{hours.toFixed(1)}h</span>
                                        </div>
                                        <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                            <div 
                                                className="h-full bg-[#D4AF37] rounded-full" 
                                                style={{ width: `${Math.min((hours / totalHours) * 100, 100)}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        ) : (
            <div className="flex flex-col items-center justify-center py-16 text-gray-400 bg-white border border-dashed rounded-lg">
                <User className="w-16 h-16 mb-4 opacity-20" />
                <p>Select an employee above to view their time sheet details.</p>
            </div>
        )}
    </div>
  );
};

export default AdminEmployeeTimeSheetPage;