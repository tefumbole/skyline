import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useToast } from '@/components/ui/use-toast';
import { getScheduledTasks, sendScheduledTaskNow, deleteTask, getTaskDetails } from '@/services/taskService';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2, Calendar, Clock, Plus, Send, Users, Pencil, Trash2 } from 'lucide-react';
import { format } from 'date-fns';
import { getPriorityColor, getStatusColor } from '@/components/admin/TaskDashboardCard';
import TaskBulkActionsBar from '@/components/user/TaskBulkActionsBar';
import EditTaskModal from '@/components/admin/EditTaskModal';

const ScheduledTasksPage = () => {
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [sendingId, setSendingId] = useState(null);
  const [selectedIds, setSelectedIds] = useState([]);
  const [bulkDeleting, setBulkDeleting] = useState(false);
  const [editTask, setEditTask] = useState(null);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const { toast } = useToast();

  const loadTasks = async () => {
    setLoading(true);
    const res = await getScheduledTasks();
    if (res.success) setTasks(res.data);
    else toast({ title: 'Error', description: res.error, variant: 'destructive' });
    setLoading(false);
  };

  useEffect(() => {
    loadTasks();
  }, []);

  const toggleSelection = (id) => {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const handleSelectAll = (checked) => {
    setSelectedIds(checked ? tasks.map((t) => t.id) : []);
  };

  const handleBulkDelete = async (ids = selectedIds) => {
    if (!ids.length) return;
    if (!window.confirm(`Delete ${ids.length} scheduled task(s)? Their pending WhatsApp sends will be cancelled.`)) return;
    setBulkDeleting(true);
    let ok = 0;
    for (const id of ids) {
      // eslint-disable-next-line no-await-in-loop
      const res = await deleteTask(id);
      if (res.success) ok += 1;
    }
    setBulkDeleting(false);
    toast({ title: 'Scheduled tasks deleted', description: `${ok} task(s) removed.` });
    setSelectedIds([]);
    loadTasks();
  };

  const handleEdit = async (taskId) => {
    const res = await getTaskDetails(taskId);
    if (res.success) {
      setEditTask(res.data);
      setIsEditOpen(true);
    } else {
      toast({ title: 'Could not open task', description: res.error, variant: 'destructive' });
    }
  };

  const handleSendNow = async (taskId, title) => {
    if (!window.confirm(`Send "${title}" to all assignees now? Remaining scheduled sends will be cancelled.`)) return;
    setSendingId(taskId);
    const res = await sendScheduledTaskNow(taskId);
    setSendingId(null);
    if (res.success) {
      toast({
        title: 'Task sent',
        description: `WhatsApp sent to ${res.sent} assignee(s). Task is now active.`,
      });
      loadTasks();
    } else {
      toast({ title: 'Send failed', description: res.error || 'Could not send task', variant: 'destructive' });
    }
  };

  return (
    <div className="max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col sm:flex-row justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Scheduled Tasks</h1>
          <p className="text-gray-500">Tasks waiting to send WhatsApp notifications at the scheduled times.</p>
        </div>
        <Link to="/admin/tasks/create">
          <Button className="bg-[#003D82]"><Plus className="w-4 h-4 mr-2" /> Create Task</Button>
        </Link>
      </div>

      {!loading && tasks.length > 0 && (
        <TaskBulkActionsBar
          totalCount={tasks.length}
          selectedIds={selectedIds}
          onSelectAll={handleSelectAll}
          onClearSelection={() => setSelectedIds([])}
          onDeleteSelected={() => handleBulkDelete()}
          deleting={bulkDeleting}
          deleteLabel="Delete Selected"
        />
      )}

      {loading ? (
        <div className="flex justify-center py-16"><Loader2 className="w-8 h-8 animate-spin text-[#003D82]" /></div>
      ) : tasks.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center text-gray-500">
            <Clock className="w-10 h-10 mx-auto mb-3 opacity-40" />
            <p>No scheduled tasks. Use <strong>Schedule</strong> or <strong>Multi Schedule</strong> when creating tasks.</p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {tasks.map((task) => (
            <Card key={task.id} className="overflow-hidden" style={{ borderLeftWidth: 4, borderLeftColor: task.color || '#003D82' }}>
              <CardHeader className="pb-2">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="flex items-start gap-3">
                    <Checkbox
                      checked={selectedIds.includes(task.id)}
                      onCheckedChange={() => toggleSelection(task.id)}
                      className="mt-1"
                      aria-label={`Select ${task.title}`}
                    />
                    <div>
                      <h3 className="text-lg font-bold text-gray-900">{task.title}</h3>
                      <p className="text-sm text-gray-500 mt-1 line-clamp-2">{task.description}</p>
                    </div>
                  </div>
                  <div className="flex gap-2 flex-wrap items-start">
                    <Badge className={getPriorityColor(task.priority)}>{task.priority}</Badge>
                    <Badge className={getStatusColor(task.status || 'Scheduled')}>{task.status || 'Scheduled'}</Badge>
                    <Button
                      size="sm"
                      variant="outline"
                      className="text-[#003D82] border-[#003D82]/30"
                      onClick={() => handleEdit(task.id)}
                    >
                      <Pencil className="w-4 h-4 mr-1" /> Edit
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      className="text-red-600 border-red-200 hover:bg-red-50"
                      disabled={bulkDeleting}
                      onClick={() => handleBulkDelete([task.id])}
                    >
                      <Trash2 className="w-4 h-4 mr-1" /> Delete
                    </Button>
                    <Button
                      size="sm"
                      className="bg-[#003D82] hover:bg-[#002a5a] ml-auto"
                      disabled={sendingId === task.id || !(task.task_assignments?.length)}
                      onClick={() => handleSendNow(task.id, task.title)}
                    >
                      {sendingId === task.id ? (
                        <Loader2 className="w-4 h-4 animate-spin" />
                      ) : (
                        <>
                          <Send className="w-4 h-4 mr-1" /> Send Now
                        </>
                      )}
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex flex-wrap gap-4 text-sm text-gray-600">
                  {task.deadline && (
                    <span className="flex items-center gap-1"><Calendar className="w-4 h-4" /> Due {format(new Date(task.deadline), 'MMM dd, yyyy')}{task.deadline_time ? ` ${String(task.deadline_time).slice(0, 5)}` : ''}</span>
                  )}
                  <span className="flex items-center gap-1"><Users className="w-4 h-4" /> {task.task_assignments?.length || 0} assignee(s)</span>
                </div>

                {task.pending_notifications?.length > 0 ? (
                  <div className="rounded-lg bg-amber-50 border border-amber-100 p-3">
                    <p className="text-xs font-semibold text-amber-900 mb-2 flex items-center gap-1"><Send className="w-3.5 h-3.5" /> Pending sends</p>
                    <ul className="space-y-1">
                      {task.pending_notifications.map((n) => (
                        <li key={n.id} className="text-sm text-amber-800 flex items-center gap-2">
                          <Clock className="w-3.5 h-3.5 shrink-0" />
                          {n.scheduled_at ? format(new Date(n.scheduled_at), 'MMM dd, yyyy · HH:mm') : '—'}
                          <Badge variant="outline" className="text-xs capitalize">{n.status}</Badge>
                        </li>
                      ))}
                    </ul>
                  </div>
                ) : task.schedules_json ? (
                  <div className="rounded-lg bg-gray-50 border p-3 text-sm text-gray-700">
                    <p className="text-xs font-semibold text-gray-500 mb-1">Schedule times</p>
                    {(() => {
                      try {
                        const times = typeof task.schedules_json === 'string'
                          ? JSON.parse(task.schedules_json)
                          : task.schedules_json;
                        return (Array.isArray(times) ? times : []).map((t, i) => (
                          <div key={i} className="flex items-center gap-2"><Clock className="w-3.5 h-3.5" /> {format(new Date(t), 'MMM dd, yyyy · HH:mm')}</div>
                        ));
                      } catch {
                        return <span className="text-xs text-gray-400">Schedule data unavailable</span>;
                      }
                    })()}
                  </div>
                ) : null}
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <EditTaskModal
        isOpen={isEditOpen}
        onClose={() => setIsEditOpen(false)}
        task={editTask}
        onSave={loadTasks}
      />
    </div>
  );
};

export default ScheduledTasksPage;
