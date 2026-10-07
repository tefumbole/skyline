import React from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart3, TrendingUp, AlertTriangle } from 'lucide-react';

const AdminTimeSheetReportsPage = () => {
    const { entries } = useTimeSheet();

    // Calculate Overtime (Simple logic: > 8 hours in a single entry for simplicity in this demo)
    // In real app, would aggregate by week
    const overtimeEntries = entries.filter(e => Number(e.hours) > 8);

    const totalHours = entries.reduce((acc, curr) => acc + Number(curr.hours), 0);
    
    // Group by month
    const monthlyStats = entries.reduce((acc, curr) => {
        const month = new Date(curr.date).toLocaleString('default', { month: 'long' });
        acc[month] = (acc[month] || 0) + Number(curr.hours);
        return acc;
    }, {});

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82]">Time Sheet Reports</h1>
                <p className="text-gray-500">Analytics and insights on workforce time allocation.</p>
            </div>

            <div className="grid md:grid-cols-3 gap-6">
                <Card className="bg-gradient-to-br from-[#003D82] to-[#002855] text-white shadow-lg transform hover:scale-[1.02] transition-all">
                    <CardHeader><CardTitle className="text-white/80">Total Hours Logged</CardTitle></CardHeader>
                    <CardContent>
                        <div className="text-4xl font-bold flex items-center gap-2">
                            {totalHours.toFixed(1)} <span className="text-lg opacity-60">hrs</span>
                        </div>
                        <div className="mt-4 flex items-center text-sm text-green-300">
                            <TrendingUp className="w-4 h-4 mr-1" /> +12% vs last month
                        </div>
                    </CardContent>
                </Card>
                
                <Card className="shadow-lg hover:shadow-xl transition-shadow">
                    <CardHeader><CardTitle className="text-gray-600">Overtime Incidents</CardTitle></CardHeader>
                    <CardContent>
                         <div className="text-4xl font-bold text-red-600 flex items-center gap-2">
                            {overtimeEntries.length} <span className="text-lg text-gray-400 font-normal">entries</span>
                        </div>
                        <div className="mt-4 flex items-center text-sm text-red-500">
                            <AlertTriangle className="w-4 h-4 mr-1" /> &gt; 8 hours/day
                        </div>
                    </CardContent>
                </Card>
                
                <Card className="shadow-lg hover:shadow-xl transition-shadow">
                    <CardHeader><CardTitle className="text-gray-600">Active Workforce</CardTitle></CardHeader>
                    <CardContent>
                         <div className="text-4xl font-bold text-[#D4AF37] flex items-center gap-2">
                            {new Set(entries.map(e => e.userId)).size} <span className="text-lg text-gray-400 font-normal">employees</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <BarChart3 className="w-5 h-5 text-[#003D82]" /> Monthly Trend
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="h-64 flex items-end gap-4 p-4 border-b border-l border-gray-200">
                        {Object.entries(monthlyStats).map(([month, hours], idx) => (
                            <div key={idx} className="flex-1 flex flex-col justify-end items-center gap-2 group">
                                <div className="text-xs font-bold text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity">{hours.toFixed(0)}h</div>
                                <div 
                                    className="w-full bg-[#003D82] rounded-t-sm hover:bg-[#D4AF37] transition-colors relative"
                                    style={{ height: `${Math.max((hours / totalHours) * 100 * 2, 10)}%` }} // Scaling factor for visual
                                ></div>
                                <div className="text-xs text-gray-500 truncate w-full text-center">{month.substring(0,3)}</div>
                            </div>
                        ))}
                        {Object.keys(monthlyStats).length === 0 && <div className="w-full text-center text-gray-400">No data available</div>}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default AdminTimeSheetReportsPage;