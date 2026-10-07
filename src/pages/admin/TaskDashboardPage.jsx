import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useToast } from '@/components/ui/use-toast';
import { getTaskStats, checkAndUpdateOverdueTasks } from '@/services/taskService';
import { processScheduledTaskNotifications, processTaskReminders } from '@/services/taskNotificationService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Loader2, ListTodo, AlertCircle, Clock, CheckCircle, RefreshCcw, BarChart3 } from 'lucide-react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';

const CHART_COLORS = {
  Pending: '#94a3b8',
  'In Progress': '#eab308',
  Completed: '#22c55e',
  Overdue: '#ef4444',
};

const TaskDashboardPage = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const { toast } = useToast();

  useEffect(() => {
    initDashboard();
  }, []);

  useEffect(() => {
    if (import.meta.env.VITE_DATA_BACKEND !== 'mysql') return undefined;
    processScheduledTaskNotifications().catch(() => {});
    processTaskReminders().catch(() => {});
    const interval = setInterval(() => {
      processScheduledTaskNotifications().catch(() => {});
      processTaskReminders().catch(() => {});
    }, 60000);
    return () => clearInterval(interval);
  }, []);

  const initDashboard = async () => {
    setLoading(true);
    await checkAndUpdateOverdueTasks();
    const res = await getTaskStats();
    if (res.success) setStats(res.data);
    else toast({ title: 'Error loading stats', description: res.error, variant: 'destructive' });
    setLoading(false);
  };

  const chartData = stats
    ? [
        { name: 'Pending', value: stats.pending, fill: CHART_COLORS.Pending },
        { name: 'In Progress', value: stats.inProgress, fill: CHART_COLORS['In Progress'] },
        { name: 'Completed', value: stats.completed, fill: CHART_COLORS.Completed },
        { name: 'Overdue', value: stats.overdue, fill: CHART_COLORS.Overdue },
      ]
    : [];

  const StatCard = ({ title, value, icon: Icon, colorClass, to }) => (
    <Link to={to} className="block group">
      <Card className="transition-all hover:shadow-md hover:ring-2 hover:ring-[#003D82]/20 cursor-pointer h-full">
        <CardContent className="p-6 flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-gray-500 group-hover:text-[#003D82]">{title}</p>
            <h3 className="text-3xl font-bold mt-1 text-gray-900">{value || 0}</h3>
            <p className="text-xs text-[#003D82] mt-2 opacity-0 group-hover:opacity-100 transition-opacity">View details →</p>
          </div>
          <div className={`p-3 rounded-full ${colorClass}`}>
            <Icon className="w-6 h-6" />
          </div>
        </CardContent>
      </Card>
    </Link>
  );

  return (
    <div className="max-w-7xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Task Dashboard</h1>
          <p className="text-gray-500">Overview of team assignments — click a stat to open that list.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={initDashboard} disabled={loading}>
            <RefreshCcw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} /> Refresh
          </Button>
          <Link to="/admin/tasks/create">
            <Button className="bg-[#003D82]"><ListTodo className="w-4 h-4 mr-2" /> New Task</Button>
          </Link>
        </div>
      </div>

      {loading && !stats ? (
        <div className="flex justify-center py-20">
          <Loader2 className="w-10 h-10 animate-spin text-[#003D82]" />
        </div>
      ) : stats && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
            <StatCard title="Total Tasks" value={stats.total} icon={ListTodo} colorClass="bg-blue-100 text-blue-600" to="/admin/tasks" />
            <StatCard title="Pending" value={stats.pending} icon={Clock} colorClass="bg-gray-100 text-gray-600" to="/admin/tasks?tab=uncompleted&status=Pending" />
            <StatCard title="In Progress" value={stats.inProgress} icon={RefreshCcw} colorClass="bg-yellow-100 text-yellow-600" to="/admin/tasks?tab=uncompleted&status=In Progress" />
            <StatCard title="Completed" value={stats.completed} icon={CheckCircle} colorClass="bg-green-100 text-green-600" to="/admin/tasks?tab=completed" />
            <StatCard title="Overdue" value={stats.overdue} icon={AlertCircle} colorClass="bg-red-100 text-red-600" to="/admin/tasks?tab=overdue" />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-lg flex items-center gap-2 text-[#003D82]">
                  <BarChart3 className="w-5 h-5" /> Tasks by Status
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-72">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={chartData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                      <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                      <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                      <Tooltip />
                      <Bar dataKey="value" radius={[6, 6, 0, 0]}>
                        {chartData.map((entry) => (
                          <Cell key={entry.name} fill={entry.fill} />
                        ))}
                      </Bar>
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-lg flex items-center gap-2 text-[#003D82]">
                  <BarChart3 className="w-5 h-5" /> Status Distribution
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-72">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={chartData.filter((d) => d.value > 0)}
                        dataKey="value"
                        nameKey="name"
                        cx="50%"
                        cy="50%"
                        outerRadius={90}
                        label={({ name, value }) => `${name}: ${value}`}
                      >
                        {chartData.filter((d) => d.value > 0).map((entry) => (
                          <Cell key={entry.name} fill={entry.fill} />
                        ))}
                      </Pie>
                      <Tooltip />
                      <Legend />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
                <p className="text-center text-sm text-gray-500 mt-2">
                  Total active workload: <strong>{stats.pending + stats.inProgress + stats.overdue}</strong> open tasks
                </p>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
};

export default TaskDashboardPage;
