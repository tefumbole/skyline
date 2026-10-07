import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { Loader2 } from 'lucide-react';
import { createJob, updateJob } from '@/services/jobsService';

const JobFormModal = ({ isOpen, onClose, editJob, onSuccess }) => {
  const { toast } = useToast();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    salary: '',
    min_requirements: '',
    requirements: '',
    qualifications: '',
    responsibilities: '',
    deadline: '',
    max_applicants: '',
    max_positions: '1',
    employment_type: 'Full-Time',
    enable_countdown: false,
    status: 'open'
  });

  useEffect(() => {
    if (editJob) {
      setFormData({
        ...editJob,
        deadline: editJob.deadline ? editJob.deadline.split('T')[0] : '',
        salary: editJob.salary || '',
        max_applicants: editJob.max_applicants || '',
        max_positions: editJob.max_positions || '1',
        employment_type: editJob.employment_type || 'Full-Time',
        enable_countdown: editJob.enable_countdown || false,
        status: editJob.status || 'open',
        requirements: editJob.requirements || '',
        qualifications: editJob.qualifications || '',
        responsibilities: editJob.responsibilities || '',
        min_requirements: editJob.min_requirements || ''
      });
    } else {
      setFormData({
        title: '',
        description: '',
        salary: '',
        min_requirements: '',
        requirements: '',
        qualifications: '',
        responsibilities: '',
        deadline: '',
        max_applicants: '',
        max_positions: '1',
        employment_type: 'Full-Time',
        enable_countdown: false,
        status: 'open'
      });
    }
  }, [editJob, isOpen]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleSelectChange = (name, value) => {
    setFormData(prev => ({
        ...prev,
        [name]: value
    }));
  };

  const validateForm = () => {
      if (formData.deadline) {
          const selectedDate = new Date(formData.deadline);
          const today = new Date();
          today.setHours(0,0,0,0);
          if (selectedDate < today && formData.status === 'open' && !editJob) {
              toast({ title: "Validation Error", description: "Deadline cannot be in the past for new open jobs.", variant: "destructive" });
              return false;
          }
      }
      return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    setLoading(true);

    try {
      let status = formData.status;
      if (formData.deadline && new Date(formData.deadline) < new Date()) {
          status = 'closed';
      }

      const payload = {
          ...formData,
          status,
          // If salary is a number string, parse it, otherwise keep as string (for ranges)
          salary: isNaN(Number(formData.salary)) ? formData.salary : Number(formData.salary),
          max_applicants: formData.max_applicants ? Number(formData.max_applicants) : null,
          max_positions: formData.max_positions ? Number(formData.max_positions) : 1
      };

      if (editJob) {
        await updateJob(editJob.id, payload);
        toast({ title: "Success", description: "Job updated successfully" });
      } else {
        await createJob(payload);
        toast({ title: "Success", description: "Job created successfully" });
      }
      onSuccess();
      onClose();
    } catch (error) {
      console.error(error);
      toast({ title: "Error", description: "Failed to save job", variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{editJob ? 'Edit Job' : 'Create New Job'}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="title">Job Title <span className="text-red-500">*</span></Label>
              <Input id="title" name="title" value={formData.title} onChange={handleChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="salary">Salary (RWF)</Label>
              <Input id="salary" name="salary" type="text" value={formData.salary} onChange={handleChange} placeholder="e.g. 50000 or 50000-70000" />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Job Description</Label>
            <Textarea id="description" name="description" value={formData.description} onChange={handleChange} rows={4} />
          </div>

          <div className="space-y-2">
              <Label htmlFor="employment_type">Employment Type <span className="text-red-500">*</span></Label>
              <Select 
                  value={formData.employment_type} 
                  onValueChange={(val) => handleSelectChange('employment_type', val)}
              >
                  <SelectTrigger>
                      <SelectValue placeholder="Select type" />
                  </SelectTrigger>
                  <SelectContent>
                      <SelectItem value="Full-Time">Full-Time</SelectItem>
                      <SelectItem value="Part-Time">Part-Time</SelectItem>
                      <SelectItem value="Contract">Contract</SelectItem>
                  </SelectContent>
              </Select>
          </div>
          
          <div className="grid grid-cols-2 gap-4">
             <div className="space-y-2">
                <Label htmlFor="responsibilities">Responsibilities</Label>
                <Textarea id="responsibilities" name="responsibilities" value={formData.responsibilities} onChange={handleChange} rows={3} />
            </div>
            <div className="space-y-2">
                <Label htmlFor="requirements">Requirements</Label>
                <Textarea id="requirements" name="requirements" value={formData.requirements} onChange={handleChange} rows={3} />
            </div>
          </div>
          
          <div className="space-y-2">
              <Label htmlFor="qualifications">Qualifications</Label>
              <Textarea id="qualifications" name="qualifications" value={formData.qualifications} onChange={handleChange} rows={2} />
          </div>

          <div className="grid grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border">
            <div className="space-y-2">
              <Label htmlFor="deadline">Deadline</Label>
              <Input id="deadline" name="deadline" type="date" value={formData.deadline} onChange={handleChange} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="max_applicants">Max Applicants (Limit)</Label>
              <Input id="max_applicants" name="max_applicants" type="number" value={formData.max_applicants} onChange={handleChange} />
            </div>
             <div className="space-y-2">
              <Label htmlFor="max_positions">Maximum Positions Available</Label>
              <Input id="max_positions" name="max_positions" type="number" value={formData.max_positions} onChange={handleChange} min="1" required />
            </div>
          </div>

          <div className="flex items-center space-x-2">
            <Checkbox 
                id="enable_countdown" 
                name="enable_countdown"
                checked={formData.enable_countdown} 
                onCheckedChange={(checked) => setFormData(prev => ({...prev, enable_countdown: checked}))}
            />
            <Label htmlFor="enable_countdown">Enable Countdown Timer</Label>
          </div>

          <div className="space-y-2">
            <Label htmlFor="status">Status</Label>
            <select 
                id="status" 
                name="status" 
                value={formData.status} 
                onChange={handleChange}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="open">Open</option>
                <option value="closed">Closed</option>
                <option value="draft">Draft</option>
            </select>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose}>Cancel</Button>
            <Button type="submit" disabled={loading} className="bg-[#003D82]">
              {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {editJob ? 'Update Job' : 'Create Job'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default JobFormModal;