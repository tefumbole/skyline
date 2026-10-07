import React, { useState, useEffect } from 'react';
import { getAllJobs, deleteJob } from '@/services/jobsService';
import JobFormModal from '@/components/admin/JobFormModal';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Edit2, Trash2, Users, Calendar, Loader2, Share2 } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { getAllMembers } from '@/services/membersService';
import { WhatsAppService } from '@/services/WhatsAppService';

const AdminJobsPage = () => {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingJob, setEditingJob] = useState(null);
  
  // WhatsApp Announcement State
  const [isAnnounceOpen, setIsAnnounceOpen] = useState(false);
  const [selectedJob, setSelectedJob] = useState(null);
  const [announceLoading, setAnnounceLoading] = useState(false);
  const [confirmAnnounce, setConfirmAnnounce] = useState(false);
  
  const { toast } = useToast();

  const fetchJobs = async () => {
    try {
      setLoading(true);
      const data = await getAllJobs();
      setJobs(data);
    } catch (error) {
      toast({ title: "Error", description: "Failed to load jobs", variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchJobs();
  }, []);

  const handleDelete = async (id) => {
    try {
      await deleteJob(id);
      toast({ title: "Success", description: "Job deleted successfully" });
      fetchJobs();
    } catch (error) {
      toast({ title: "Error", description: "Failed to delete job", variant: "destructive" });
    }
  };

  const handleEdit = (job) => {
    setEditingJob(job);
    setIsModalOpen(true);
  };

  const handleCreate = () => {
    setEditingJob(null);
    setIsModalOpen(true);
  };

  const openAnnounceModal = (job) => {
      setSelectedJob(job);
      setIsAnnounceOpen(true);
      setConfirmAnnounce(false);
  };

  const handleSendAnnouncement = async () => {
      if (!confirmAnnounce) return;
      setAnnounceLoading(true);
      try {
          // Get all members to notify - simplified to notify all members for now as per prompt implication "groups"
          // In a real scenario, you'd select specific groups.
          const members = await getAllMembers();
          const recipients = members
              .filter(m => m.phone || m.phone_number)
              .map(m => ({
                  phone: m.phone || m.phone_number,
                  name: m.name,
                  message: `New job opportunity: ${selectedJob.title} - ${selectedJob.location || 'Remote/On-site'}. Apply now!`,
                  type: 'member'
              }));
          
          if (recipients.length === 0) {
              toast({ title: "No Recipients", description: "No members with phone numbers found.", variant: "destructive" });
              return;
          }

          const result = await WhatsAppService.sendBatchMessage(recipients, 'job_posting');
          toast({ 
              title: "Announcement Sent", 
              description: `Successfully sent to ${result.sent} members (${result.failed} failed).` 
          });
          setIsAnnounceOpen(false);
      } catch (error) {
          toast({ title: "Error", description: "Failed to send announcements", variant: "destructive" });
      } finally {
          setAnnounceLoading(false);
      }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Jobs Management</h1>
            <p className="text-gray-500">Manage open positions, deadlines, and requirements.</p>
        </div>
        <Button onClick={handleCreate} className="bg-[#D4AF37] text-[#003D82] hover:bg-[#b5952f] font-bold">
          <Plus className="w-4 h-4 mr-2" /> Post New Job
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Job Listings</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
             <div className="flex justify-center p-8"><Loader2 className="w-8 h-8 animate-spin text-blue-600" /></div>
          ) : jobs.length === 0 ? (
             <div className="text-center p-8 text-gray-500">No jobs posted yet.</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50">
                  <TableHead>Job Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Max Positions</TableHead>
                  <TableHead>Deadline</TableHead>
                  <TableHead>Applicants</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {jobs.map((job) => (
                  <TableRow key={job.id} className="hover:bg-gray-50 transition-colors">
                    <TableCell className="font-medium text-[#003D82]">
                        {job.title}
                        <div className="text-xs text-gray-400 font-normal mt-0.5">{job.department || 'Engineering'}</div>
                    </TableCell>
                    <TableCell>
                        <Badge variant="outline" className="text-xs bg-gray-50">
                            {job.employment_type || 'Full-Time'}
                        </Badge>
                    </TableCell>
                    <TableCell>
                        <div className="text-sm font-semibold">{job.max_positions || 1}</div>
                    </TableCell>
                    <TableCell>
                        <div className="flex items-center gap-2 text-sm">
                           <Calendar className="w-4 h-4 text-gray-400" />
                           {job.deadline ? new Date(job.deadline).toLocaleDateString() : 'No Deadline'}
                        </div>
                    </TableCell>
                    <TableCell>
                        <div className="flex items-center gap-1">
                            <Users className="w-4 h-4 text-gray-400" />
                            <span>{job.current_applicants || 0}</span>
                            <span className="text-gray-400 text-xs">/ {job.max_applicants || '∞'}</span>
                        </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={job.status === 'open' ? 'default' : 'secondary'} 
                             className={job.status === 'open' ? 'bg-green-600' : 'bg-gray-400'}>
                        {job.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button 
                        variant="ghost" 
                        size="sm" 
                        onClick={() => openAnnounceModal(job)} 
                        className="text-green-600 hover:text-green-700 hover:bg-green-50"
                        title="Send WhatsApp Announcement"
                      >
                        <Share2 className="w-4 h-4" />
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(job)} className="hover:text-blue-600">
                        <Edit2 className="w-4 h-4" />
                      </Button>
                      
                      <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button variant="ghost" size="sm" className="hover:text-red-600 text-red-500">
                                <Trash2 className="w-4 h-4" />
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Delete Job?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Are you sure you want to delete <strong>{job.title}</strong>? 
                                    This action cannot be undone.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction onClick={() => handleDelete(job.id)} className="bg-red-600 hover:bg-red-700">Delete</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                      </AlertDialog>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <JobFormModal 
        isOpen={isModalOpen} 
        onClose={() => setIsModalOpen(false)} 
        editJob={editingJob} 
        onSuccess={fetchJobs} 
      />

      <Dialog open={isAnnounceOpen} onOpenChange={setIsAnnounceOpen}>
          <DialogContent>
              <DialogHeader>
                  <DialogTitle>Send Job Announcement</DialogTitle>
              </DialogHeader>
              <div className="py-4 space-y-4">
                  <p className="text-sm text-gray-600">
                      This will send a WhatsApp notification to all registered members about <strong>{selectedJob?.title}</strong>.
                  </p>
                  <div className="p-3 bg-gray-50 rounded border text-sm">
                      <strong>Preview:</strong> "New job opportunity: {selectedJob?.title} - {selectedJob?.location || 'Remote/On-site'}. Apply now!"
                  </div>
                  <div className="flex items-center space-x-2">
                      <Checkbox id="confirm" checked={confirmAnnounce} onCheckedChange={setConfirmAnnounce} />
                      <Label htmlFor="confirm">I confirm I want to send this to all members.</Label>
                  </div>
              </div>
              <DialogFooter>
                  <Button variant="outline" onClick={() => setIsAnnounceOpen(false)}>Cancel</Button>
                  <Button 
                    onClick={handleSendAnnouncement} 
                    disabled={announceLoading || !confirmAnnounce}
                    className="bg-green-600 hover:bg-green-700"
                  >
                      {announceLoading && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
                      Send Announcement
                  </Button>
              </DialogFooter>
          </DialogContent>
      </Dialog>
    </div>
  );
};

export default AdminJobsPage;