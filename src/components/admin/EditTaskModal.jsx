import React, { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { adminUpdateTask } from '@/services/taskService';
import { searchUsersForTaskAssignment } from '@/services/userService';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { X, Loader2, Search, Plus, Trash2 } from 'lucide-react';

const EditTaskModal = ({ isOpen, onClose, task, onSave }) => {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    priority: 'Medium',
    deadline: '',
    deadline_time: '',
    adminComment: '',
  });
  const [assignees, setAssignees] = useState([]);
  const [searchResults, setSearchResults] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [scheduleTimes, setScheduleTimes] = useState(['']);
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (!isOpen || !task) return;
    setFormData({
      title: task.title || '',
      description: task.description || '',
      priority: task.priority || 'Medium',
      deadline: task.deadline ? String(task.deadline).slice(0, 10) : '',
      deadline_time: task.deadline_time ? String(task.deadline_time).slice(0, 5) : '',
      adminComment: '',
    });
    setAssignees(
      (task.task_assignments || [])
        .map((a) => a.profiles)
        .filter(Boolean)
        .map((p) => ({
          id: p.id,
          name: p.full_name || p.name,
          full_name: p.full_name || p.name,
          email: p.email,
          phone: p.phone,
        }))
    );
    setScheduleTimes(['']);
    setSearchQuery('');
  }, [isOpen, task]);

  useEffect(() => {
    if (!isOpen) return undefined;
    const timer = setTimeout(async () => {
      const res = await searchUsersForTaskAssignment(searchQuery, 'all');
      setSearchResults(res.success ? (res.data || []).filter((u) => !assignees.find((a) => a.id === u.id)) : []);
    }, 300);
    return () => clearTimeout(timer);
  }, [searchQuery, isOpen, assignees]);

  const addAssignee = (user) => {
    if (assignees.find((a) => a.id === user.id)) return;
    setAssignees([...assignees, user]);
    setSearchQuery('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.title || !formData.deadline) {
      toast({ title: 'Validation Error', description: 'Title and deadline are required.', variant: 'destructive' });
      return;
    }
    if (!assignees.length) {
      toast({ title: 'Validation Error', description: 'At least one assignee is required.', variant: 'destructive' });
      return;
    }

    setLoading(true);
    const res = await adminUpdateTask(task.id, {
      title: formData.title,
      description: formData.description,
      priority: formData.priority,
      deadline: formData.deadline,
      deadline_time: formData.deadline_time || null,
      assigneeIds: assignees.map((a) => a.id),
      adminComment: formData.adminComment,
      scheduleTimes: scheduleTimes.filter(Boolean),
    });
    setLoading(false);

    if (res.success) {
      toast({
        title: 'Task updated',
        description: `Changes saved. ${res.added ? `${res.added} new assignee(s) notified.` : 'Assignees updated.'}`,
      });
      onSave();
      onClose();
    } else {
      toast({ title: 'Error', description: res.error, variant: 'destructive' });
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[640px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Task</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4 py-2">
          <div className="space-y-2">
            <Label>Title *</Label>
            <Input value={formData.title} onChange={(e) => setFormData({ ...formData, title: e.target.value })} />
          </div>

          <div className="space-y-2">
            <Label>Description</Label>
            <Textarea rows={3} value={formData.description} onChange={(e) => setFormData({ ...formData, description: e.target.value })} />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Priority</Label>
              <Select value={formData.priority} onValueChange={(v) => setFormData({ ...formData, priority: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {['Low', 'Medium', 'High', 'Emergency', 'Critical'].map((p) => (
                    <SelectItem key={p} value={p}>{p}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>End Date *</Label>
              <Input type="date" value={formData.deadline} onChange={(e) => setFormData({ ...formData, deadline: e.target.value })} />
            </div>
          </div>

          <div className="space-y-2">
            <Label>End Time</Label>
            <Input type="time" value={formData.deadline_time} onChange={(e) => setFormData({ ...formData, deadline_time: e.target.value })} />
          </div>

          <div className="space-y-2 border-t pt-3">
            <Label>Assignees *</Label>
            <div className="relative">
              <Search className="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <Input className="pl-8" placeholder="Search to add people..." value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
            </div>
            {searchResults.length > 0 && (
              <div className="max-h-32 overflow-auto border rounded-md">
                {searchResults.slice(0, 8).map((user) => (
                  <button key={user.id} type="button" onClick={() => addAssignee(user)} className="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 border-b last:border-0">
                    {user.name || user.full_name}
                  </button>
                ))}
              </div>
            )}
            <div className="flex flex-wrap gap-2">
              {assignees.map((user) => (
                <Badge key={user.id} variant="outline" className="gap-1">
                  {user.name || user.full_name}
                  <X className="w-3 h-3 cursor-pointer" onClick={() => setAssignees(assignees.filter((a) => a.id !== user.id))} />
                </Badge>
              ))}
            </div>
          </div>

          <div className="space-y-2 border-t pt-3">
            <Label>Schedule additional sends (optional)</Label>
            {scheduleTimes.map((timeVal, i) => (
              <div key={i} className="flex gap-2">
                <Input type="datetime-local" value={timeVal} onChange={(e) => {
                  const next = [...scheduleTimes];
                  next[i] = e.target.value;
                  setScheduleTimes(next);
                }} />
                {scheduleTimes.length > 1 && (
                  <Button type="button" variant="ghost" size="icon" onClick={() => setScheduleTimes(scheduleTimes.filter((_, idx) => idx !== i))}>
                    <Trash2 className="w-4 h-4 text-red-500" />
                  </Button>
                )}
              </div>
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => setScheduleTimes([...scheduleTimes, ''])}>
              <Plus className="w-3 h-3 mr-1" /> Add schedule time
            </Button>
          </div>

          <div className="space-y-2 border-t pt-3">
            <Label>Message to assignees (WhatsApp)</Label>
            <Textarea
              rows={2}
              placeholder="Optional comment — all current assignees will receive this on WhatsApp."
              value={formData.adminComment}
              onChange={(e) => setFormData({ ...formData, adminComment: e.target.value })}
            />
          </div>

          <DialogFooter className="mt-4">
            <Button type="button" variant="outline" onClick={onClose} disabled={loading}>Cancel</Button>
            <Button type="submit" className="bg-[#003D82]" disabled={loading}>
              {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Save & Notify'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default EditTaskModal;
