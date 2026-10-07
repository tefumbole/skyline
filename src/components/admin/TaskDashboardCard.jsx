import React, { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { format } from 'date-fns';
import { ChevronDown, ChevronUp, Calendar, AlertCircle, CheckCircle, Clock, Loader2, MessageSquare } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/use-toast';
import { useAuth } from '@/context/AuthContext';
import { adminReviewAssignment } from '@/services/taskService';
import { getEffectiveTaskStatus } from '@/utils/taskDeadline';

export const getStatusColor = (status) => {
  switch (status) {
    case 'Completed': return 'bg-green-100 text-green-800 border-green-200';
    case 'In Progress': return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'Overdue': return 'bg-red-100 text-red-800 border-red-200';
    default: return 'bg-gray-100 text-gray-800 border-gray-200';
  }
};

export const getStatusIcon = (status) => {
  switch (status) {
    case 'Completed': return <CheckCircle className="w-3 h-3 mr-1" />;
    case 'Overdue': return <AlertCircle className="w-3 h-3 mr-1" />;
    case 'In Progress': return <Clock className="w-3 h-3 mr-1" />;
    default: return <Clock className="w-3 h-3 mr-1" />;
  }
};

export const getPriorityColor = (priority) => {
  switch (priority) {
    case 'Critical': return 'bg-red-500 text-white';
    case 'High': return 'bg-orange-500 text-white';
    case 'Medium': return 'bg-yellow-500 text-white';
    default: return 'bg-green-500 text-white';
  }
};

const AssignmentReviewPanel = ({ assignment, taskId, onReviewed }) => {
  const [comment, setComment] = useState('');
  const [saving, setSaving] = useState(false);
  const { toast } = useToast();
  const { user, profile } = useAuth();
  const adminName = profile?.full_name || user?.email || 'Admin';

  const submitReview = async (progress) => {
    if (!comment.trim() && progress < 100) {
      toast({ title: 'Comment required', description: 'Add feedback when sending the task back for revision.', variant: 'destructive' });
      return;
    }

    setSaving(true);
    const res = await adminReviewAssignment(assignment.id, taskId, progress, comment, adminName);
    if (res.success) {
      toast({
        title: progress >= 100 ? 'Task marked completed' : `Sent back at ${progress}%`,
        description: 'The assignee has been notified on WhatsApp.',
      });
      setComment('');
      onReviewed?.();
    } else {
      toast({ title: 'Review failed', description: res.error, variant: 'destructive' });
    }
    setSaving(false);
  };

  if (assignment.status === 'Pending' || assignment.status === 'Declined') {
    return null;
  }

  return (
    <div className="mt-3 rounded border border-amber-100 bg-amber-50/60 p-3 space-y-2">
      <p className="text-xs font-semibold text-amber-800 flex items-center gap-1">
        <MessageSquare className="w-3 h-3" /> Admin Review
      </p>
      <Textarea
        rows={2}
        placeholder="Comments for the assignee..."
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        className="bg-white text-sm"
      />
      <div className="flex flex-wrap gap-2">
        <Button type="button" size="sm" variant="outline" disabled={saving} onClick={() => submitReview(60)}>
          {saving ? <Loader2 className="w-3 h-3 animate-spin" /> : 'Set 60%'}
        </Button>
        <Button type="button" size="sm" variant="outline" disabled={saving} onClick={() => submitReview(80)}>
          Set 80%
        </Button>
        <Button type="button" size="sm" className="bg-green-600 hover:bg-green-700 text-white" disabled={saving} onClick={() => submitReview(100)}>
          Mark Completed
        </Button>
      </div>
    </div>
  );
};

const TaskDashboardCard = ({ task, onTaskUpdated }) => {
  const [expanded, setExpanded] = useState(false);
  const displayStatus = getEffectiveTaskStatus(task);

  return (
    <Card className={`overflow-hidden transition-all duration-200 ${expanded ? 'shadow-md ring-1 ring-blue-100' : 'hover:shadow-md'}`}>
      <div
        className="p-4 cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4"
        onClick={() => setExpanded(!expanded)}
      >
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-1">
            <h3 className="font-semibold text-lg text-gray-900 truncate">{task.title}</h3>
            <Badge className={`${getPriorityColor(task.priority)} border-none text-xs px-2 py-0`}>
              {task.priority}
            </Badge>
          </div>
          <div className="flex items-center text-xs text-gray-500 gap-4">
             <span className="flex items-center"><Calendar className="w-3 h-3 mr-1"/> Due: {task.deadline ? format(new Date(task.deadline), 'MMM dd, yyyy') : 'No Date'}{task.deadline_time ? ` ${String(task.deadline_time).slice(0, 5)}` : ''}</span>
             <span>Assignees: {task.assignments_count}</span>
          </div>
        </div>

        <div className="flex items-center gap-6 w-full md:w-auto">
          <div className="flex-1 md:w-48">
            <div className="flex justify-between text-xs mb-1">
              <span className="font-medium">Overall Progress</span>
              <span>{task.overallProgress}%</span>
            </div>
            <Progress value={task.overallProgress} className="h-2" />
          </div>

          <Badge className={getStatusColor(displayStatus)}>
            {getStatusIcon(displayStatus)}
            {displayStatus}
          </Badge>

          <Button variant="ghost" size="sm" className="p-1 h-8 w-8 rounded-full">
            {expanded ? <ChevronUp className="w-5 h-5 text-gray-500" /> : <ChevronDown className="w-5 h-5 text-gray-500" />}
          </Button>
        </div>
      </div>

      {expanded && (
        <CardContent className="bg-gray-50 border-t p-4">
          <div className="mb-4">
            <h4 className="text-xs font-semibold text-gray-500 uppercase mb-1">Description</h4>
            <p className="text-sm text-gray-700 whitespace-pre-wrap">{task.description || 'No description provided.'}</p>
          </div>

          <div>
            <h4 className="text-xs font-semibold text-gray-500 uppercase mb-2">Assignee Status</h4>
            {task.task_assignments && task.task_assignments.length > 0 ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {task.task_assignments.map(assignment => (
                  <div key={assignment.id} className="bg-white p-3 rounded border text-sm flex flex-col gap-2">
                    <div className="flex justify-between items-center">
                      <span className="font-medium">{assignment.profiles?.full_name || 'Unknown User'}</span>
                      <Badge variant="outline" className={`text-[10px] ${assignment.status === 'Completed' ? 'bg-green-50 text-green-700' : ''}`}>
                        {assignment.status}
                      </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                      <Progress value={assignment.progress} className="h-1.5 flex-1" />
                      <span className="text-xs text-gray-500 w-8 text-right">{assignment.progress}%</span>
                    </div>
                    <AssignmentReviewPanel
                      assignment={assignment}
                      taskId={task.id}
                      onReviewed={onTaskUpdated}
                    />
                  </div>
                ))}
              </div>
            ) : (
               <p className="text-sm text-gray-500 italic">No assignees for this task.</p>
            )}
          </div>
        </CardContent>
      )}
    </Card>
  );
};

export default TaskDashboardCard;
