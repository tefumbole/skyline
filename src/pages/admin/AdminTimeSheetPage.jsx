import React, { useState } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'; // Need to create UI table or use HTML
import { Users, Filter } from 'lucide-react';

const AdminTimeSheetPage = () => {
  const { entries, activities } = useTimeSheet();
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth().toString());
  
  // Aggregate data by user
  // Since we are mocking auth, most entries might be 'guest' or 'admin-master'
  
  const currentYear = new Date().getFullYear();
  
  const filteredEntries = entries.filter(e => {
      const d = new Date(e.date);
      return d.getMonth().toString() === selectedMonth && d.getFullYear() === currentYear;
  });

  // Group by User
  const userStats = filteredEntries.reduce((acc, curr) => {
      const userId = curr.userId || 'unknown';
      if (!acc[userId]) {
          acc[userId] = {
              name: curr.userName || 'Unknown User',
              totalHours: 0,
              activities: {}
          };
      }
      acc[userId].totalHours += Number(curr.hours);
      
      const actName = activities.find(a => a.id === curr.activityId)?.name || curr.activityName || 'Other';
      acc[userId].activities[actName] = (acc[userId].activities[actName] || 0) + Number(curr.hours);
      
      return acc;
  }, {});

  const MONTHS = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Employee Time Sheets</h1>
                <p className="text-gray-500">Administrative overview of all hours logged.</p>
            </div>
            <div className="flex items-center gap-2">
                <Filter className="w-4 h-4 text-gray-400" />
                <Select value={selectedMonth} onValueChange={setSelectedMonth}>
                    <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="Select Month" />
                    </SelectTrigger>
                    <SelectContent>
                        {MONTHS.map((m, idx) => (
                            <SelectItem key={idx} value={idx.toString()}>{m} {currentYear}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div className="grid gap-6">
            {Object.keys(userStats).length === 0 ? (
                <div className="p-12 text-center bg-white rounded-lg shadow-sm border border-gray-200">
                    <Users className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-900">No Records Found</h3>
                    <p className="text-gray-500">No time entries found for selected month.</p>
                </div>
            ) : (
                Object.values(userStats).map((user, idx) => (
                    <Card key={idx} className="overflow-hidden">
                        <CardHeader className="bg-gray-50 border-b border-gray-100 py-4">
                            <div className="flex justify-between items-center">
                                <CardTitle className="text-lg font-bold text-[#003D82] flex items-center gap-2">
                                    <div className="w-8 h-8 rounded-full bg-[#D4AF37] flex items-center justify-center text-white text-sm">
                                        {user.name.charAt(0)}
                                    </div>
                                    {user.name}
                                </CardTitle>
                                <div className="text-xl font-bold">{user.totalHours.toFixed(1)} <span className="text-sm font-normal text-gray-500">hrs</span></div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="grid md:grid-cols-2">
                                <div className="p-6 border-r border-gray-100">
                                    <h4 className="text-sm font-semibold text-gray-500 mb-4 uppercase tracking-wider">Activity Breakdown</h4>
                                    <div className="space-y-3">
                                        {Object.entries(user.activities).map(([actName, hours]) => (
                                            <div key={actName} className="flex justify-between text-sm">
                                                <span className="text-gray-700">{actName}</span>
                                                <span className="font-semibold text-gray-900">{hours.toFixed(1)}h</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                <div className="p-6 flex items-center justify-center bg-blue-50/30">
                                    {/* Placeholder for chart */}
                                    <div className="text-center">
                                        <p className="text-sm text-gray-500 italic">Detailed daily logs available in export.</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                ))
            )}
        </div>
    </div>
  );
};

export default AdminTimeSheetPage;