import React from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import ClockInOutComponent from '@/components/timesheet/ClockInOutComponent';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, BarChart3, Clock, ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';

const MonthlyHoursSummaryPage = () => {
  const { entries } = useTimeSheet();

  // Helper to get stats
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear();
  const monthName = new Date().toLocaleString('default', { month: 'long' });

  const monthlyEntries = entries.filter(e => {
      const d = new Date(e.date);
      return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
  });

  const totalHours = monthlyEntries.reduce((acc, curr) => acc + Number(curr.hours), 0);
  
  // Weekly Breakdown (Simple approximation)
  const weeklyHours = monthlyEntries.reduce((acc, curr) => {
      const d = new Date(curr.date);
      // Get week number of month
      const firstDay = new Date(d.getFullYear(), d.getMonth(), 1).getDay();
      const weekNo = Math.ceil((d.getDate() + firstDay) / 7);
      acc[weekNo] = (acc[weekNo] || 0) + Number(curr.hours);
      return acc;
  }, {});

  return (
    <div className="max-w-6xl mx-auto p-4 md:p-8 space-y-8 animate-in fade-in duration-500">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Time Sheet Dashboard</h1>
                <p className="text-gray-500">Overview for {monthName} {currentYear}</p>
            </div>
            <div className="flex gap-3">
                 <Link to="/timesheet/fill-timesheet">
                    <Button variant="outline">Log Past Hours</Button>
                 </Link>
                 <Link to="/timesheet/add-activity">
                    <Button variant="outline">Manage Activities</Button>
                 </Link>
            </div>
        </div>

        <div className="grid md:grid-cols-3 gap-6">
            <div className="md:col-span-1">
                <ClockInOutComponent />
            </div>

            <div className="md:col-span-2 grid gap-6">
                {/* Stats Cards */}
                <div className="grid grid-cols-2 gap-4">
                    <Card className="bg-[#003D82] text-white">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium opacity-80">Total Hours ({monthName})</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-4xl font-bold">{totalHours.toFixed(1)} <span className="text-lg font-normal opacity-70">hrs</span></div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-500">Entries This Month</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-4xl font-bold text-gray-800">{monthlyEntries.length}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Weekly Chart Placeholder */}
                <Card className="flex-1">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                             <BarChart3 className="w-5 h-5 text-[#D4AF37]" /> Weekly Breakdown
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {[1, 2, 3, 4, 5].map(week => {
                                const hours = weeklyHours[week] || 0;
                                const max = 40; // Assume 40h standard week for scale
                                const percent = Math.min((hours / max) * 100, 100);
                                
                                if (!hours && week > 4) return null; // Skip empty 5th week if irrelevant

                                return (
                                    <div key={week} className="space-y-1">
                                        <div className="flex justify-between text-xs font-semibold">
                                            <span>Week {week}</span>
                                            <span>{hours.toFixed(1)} hrs</span>
                                        </div>
                                        <div className="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                            <div 
                                                className="h-full bg-[#D4AF37] rounded-full" 
                                                style={{ width: `${percent}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                );
                            })}
                            {Object.keys(weeklyHours).length === 0 && (
                                <p className="text-sm text-gray-400 text-center py-4">No data available for this month.</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
  );
};

export default MonthlyHoursSummaryPage;