import React, { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { getTasks } from '@/services/taskService';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { Search, Loader2, Eye, Plus, ChevronLeft, ChevronRight } from 'lucide-react';
import { Link } from 'react-router-dom';
import { getStatusColor, getPriorityColor } from '@/components/admin/TaskDashboardCard';

const TaskListingPage = () => {
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const pageSize = 10;
  const { toast } = useToast();

  useEffect(() => {
    loadTasks();
  }, []);

  const loadTasks = async () => {
    setLoading(true);
    const res = await getTasks();
    if (res.success) {
      setTasks(res.data);
    } else {
      toast({ title: "Failed to load tasks", description: res.error, variant: "destructive" });
    }
    setLoading(false);
  };

  const filteredTasks = tasks.filter(t => t.title.toLowerCase().includes(search.toLowerCase()));
  const totalPages = Math.ceil(filteredTasks.length / pageSize);
  const paginatedTasks = filteredTasks.slice((page - 1) * pageSize, page * pageSize);

  return (
    <div className="max-w-7xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Task List</h1>
          <p className="text-gray-500">Detailed view of all system tasks.</p>
        </div>
        <Link to="/admin/tasks/create">
            <Button className="bg-[#003D82] hover:bg-[#002a5a] text-white">
                <Plus className="w-4 h-4 mr-2" /> Create Task
            </Button>
        </Link>
      </div>

      <Card>
        <CardHeader className="pb-4 border-b">
          <div className="relative max-w-md">
            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
            <Input
              placeholder="Search by task title..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPage(1); }}
              className="pl-9 bg-white text-gray-900"
            />
          </div>
        </CardHeader>
        <CardContent className="p-0">
          {loading ? (
             <div className="flex justify-center py-16">
               <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
             </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader className="bg-gray-50">
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Priority</TableHead>
                    <TableHead className="text-center">Progress</TableHead>
                    <TableHead>Deadline</TableHead>
                    <TableHead className="text-center">Assignees</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paginatedTasks.length > 0 ? paginatedTasks.map(task => (
                    <TableRow key={task.id} className="hover:bg-gray-50/50">
                      <TableCell className="font-medium text-gray-900 max-w-[250px] truncate">{task.title}</TableCell>
                      <TableCell><Badge className={getStatusColor(task.status)}>{task.status}</Badge></TableCell>
                      <TableCell><Badge className={getPriorityColor(task.priority)}>{task.priority}</Badge></TableCell>
                      <TableCell className="text-center font-medium">{task.overallProgress}%</TableCell>
                      <TableCell className="text-sm text-gray-500">{task.deadline ? format(new Date(task.deadline), 'MMM dd, yyyy') : '-'}</TableCell>
                      <TableCell className="text-center">{task.assignments_count}</TableCell>
                    </TableRow>
                  )) : (
                    <TableRow>
                        <TableCell colSpan={6} className="text-center py-8 text-gray-500">No tasks found.</TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          )}

          {totalPages > 1 && (
            <div className="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
              <span className="text-sm text-gray-500">
                Showing {(page - 1) * pageSize + 1} to {Math.min(page * pageSize, filteredTasks.length)} of {filteredTasks.length}
              </span>
              <div className="flex gap-2">
                <Button variant="outline" size="sm" onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}>
                  <ChevronLeft className="w-4 h-4 mr-1" /> Prev
                </Button>
                <div className="flex items-center px-2 text-sm font-medium">{page} / {totalPages}</div>
                <Button variant="outline" size="sm" onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages}>
                  Next <ChevronRight className="w-4 h-4 ml-1" />
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default TaskListingPage;