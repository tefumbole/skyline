import React, { useState, useEffect } from 'react';
import { getAllStudentProgress, updateProgress } from '@/services/progressService';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Search, TrendingUp, CheckCircle } from 'lucide-react';
import { Progress } from '@/components/ui/progress';

const AdminProgressPage = () => {
    const [progressData, setProgressData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const { toast } = useToast();

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const data = await getAllStudentProgress();
            setProgressData(data);
        } catch (error) {
            toast({ title: "Error", description: "Failed to load progress data.", variant: "destructive" });
        } finally {
            setLoading(false);
        }
    };

    const handleUpdate = async (item, newStatus) => {
        let newPercent = item.progress_percentage;
        if (newStatus === 'completed') newPercent = 100;
        if (newStatus === 'not_started') newPercent = 0;
        if (newStatus === 'in_progress' && newPercent === 0) newPercent = 10;

        try {
            await updateProgress(item.registration_id, item.course_id, newPercent, newStatus);
            toast({ title: "Updated", description: "Progress updated successfully." });
            loadData();
        } catch (error) {
            toast({ title: "Error", description: "Failed to update progress.", variant: "destructive" });
        }
    };

    const handlePercentChange = async (item, e) => {
        const val = parseInt(e.target.value);
        if (isNaN(val) || val < 0 || val > 100) return;
        
        // Optimistic update for UI feel could be added, here just direct
        try {
            let status = 'in_progress';
            if (val === 100) status = 'completed';
            if (val === 0) status = 'not_started';

            await updateProgress(item.registration_id, item.course_id, val, status);
            // Refresh logic - ideally update local state to avoid full reload
            loadData(); 
        } catch (error) {
             console.error(error);
        }
    };

    const filtered = progressData.filter(p => 
        p.registrations?.client_name?.toLowerCase().includes(search.toLowerCase()) || 
        p.courses?.name?.toLowerCase().includes(search.toLowerCase())
    );
    
    // Stats
    const totalCompleted = progressData.filter(p => p.status === 'completed').length;
    const avgProgress = progressData.length ? Math.round(progressData.reduce((acc, curr) => acc + (curr.progress_percentage || 0), 0) / progressData.length) : 0;

    return (
        <div className="space-y-6 animate-in fade-in duration-500">
             <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b pb-4">
                <div>
                    <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                        <TrendingUp className="w-8 h-8" /> Student Progress
                    </h1>
                    <p className="text-gray-500">Track course completion and generate certificates.</p>
                </div>
                <div className="flex gap-4">
                     <div className="bg-green-50 px-4 py-2 rounded-lg border border-green-100">
                        <span className="text-xs text-green-600 font-bold uppercase">Completed</span>
                        <div className="text-2xl font-bold text-green-800">{totalCompleted}</div>
                     </div>
                     <div className="bg-blue-50 px-4 py-2 rounded-lg border border-blue-100">
                        <span className="text-xs text-blue-600 font-bold uppercase">Avg Progress</span>
                        <div className="text-2xl font-bold text-blue-800">{avgProgress}%</div>
                     </div>
                </div>
            </div>

            <div className="flex justify-between mb-4">
                 <div className="relative w-64">
                    <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                    <Input 
                        placeholder="Search student or course..." 
                        className="pl-9" 
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                    />
                </div>
            </div>

            <div className="bg-white rounded-lg border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-gray-50">
                            <TableHead>Student</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead className="w-48">Progress</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Update</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                             <TableRow><TableCell colSpan={5} className="h-32 text-center"><Loader2 className="w-6 h-6 animate-spin mx-auto" /></TableCell></TableRow>
                        ) : filtered.length === 0 ? (
                             <TableRow><TableCell colSpan={5} className="h-32 text-center text-gray-500">No progress records found.</TableCell></TableRow>
                        ) : (
                            filtered.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.registrations?.client_name || 'Unknown'}</TableCell>
                                    <TableCell>{item.courses?.name || 'Unknown'}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <Progress value={item.progress_percentage} className="h-2 w-24" />
                                            <span className="text-xs text-gray-500">{item.progress_percentage}%</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                         <Select value={item.status} onValueChange={(val) => handleUpdate(item, val)}>
                                            <SelectTrigger className="w-[130px] h-8">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="not_started">Not Started</SelectItem>
                                                <SelectItem value="in_progress">In Progress</SelectItem>
                                                <SelectItem value="completed">Completed</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end items-center gap-2">
                                            <Input 
                                                type="number" 
                                                min="0" max="100" 
                                                className="w-16 h-8 text-center" 
                                                defaultValue={item.progress_percentage}
                                                onBlur={(e) => handlePercentChange(item, e)}
                                            />
                                            <span className="text-sm text-gray-500">%</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
};

export default AdminProgressPage;