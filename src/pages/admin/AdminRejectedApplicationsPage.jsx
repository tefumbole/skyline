import React, { useState, useEffect } from 'react';
import { getApplicationsByStatus, updateApplicationStatus } from '@/services/applicationStatusService';
import { sendApplicationRejected } from '@/services/emailService';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Loader2, RefreshCw, Eye, RotateCcw, Mail } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { useAuth } from '@/context/AuthContext';
import ApplicationReviewModal from '@/components/admin/ApplicationReviewModal';

const applicantName = (app) => app.full_name || app.candidate_name || 'Applicant';

const AdminRejectedApplicationsPage = () => {
  const [applications, setApplications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedApp, setSelectedApp] = useState(null);
  
  const [rejectionModalOpen, setRejectionModalOpen] = useState(false);
  const [rejectingApp, setRejectingApp] = useState(null);
  const [rejectionReason, setRejectionReason] = useState('');
  const [sendingEmail, setSendingEmail] = useState(false);

  const { toast } = useToast();
  const { user } = useAuth();

  const fetchApplications = async () => {
    try {
      setLoading(true);
      const data = await getApplicationsByStatus('rejected');
      setApplications(data);
    } catch (error) {
      toast({ title: "Error", description: "Failed to load rejected applications", variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchApplications();
  }, []);

  const handleRestore = async (id) => {
    try {
        await updateApplicationStatus(id, 'pending', { reason: 'Restored from rejected list' }, user?.id);
        toast({ title: "Restored", description: "Application moved back to pending" });
        fetchApplications();
    } catch (error) {
        toast({ title: "Error", description: "Failed to restore application", variant: "destructive" });
    }
  };

  const openRejectionEmailModal = (app) => {
      setRejectingApp(app);
      setRejectionReason(app.rejection_reason || '');
      setRejectionModalOpen(true);
  };

  const handleSendRejectionEmail = async () => {
      setSendingEmail(true);
      try {
          const result = await sendApplicationRejected(rejectingApp.email, rejectingApp, rejectionReason);
          if (result.success) {
            toast({ title: "Email Sent", description: `Rejection email sent to ${rejectingApp.email}` });
            setRejectionModalOpen(false);
          } else {
            throw new Error(result.error);
          }
      } catch (error) {
          toast({ title: "Failed", description: "Could not send rejection email", variant: "destructive" });
      } finally {
          setSendingEmail(false);
      }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
            <h1 className="text-3xl font-bold text-red-700">Rejected Applications</h1>
            <p className="text-gray-500">Review and manage rejected candidates.</p>
        </div>
        <Button variant="outline" onClick={fetchApplications}>
            <RefreshCw className="w-4 h-4 mr-2" /> Refresh
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
             <div className="flex justify-center p-12"><Loader2 className="w-8 h-8 animate-spin text-red-600" /></div>
          ) : applications.length === 0 ? (
             <div className="text-center p-12 text-gray-500">No rejected applications found.</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-red-50 hover:bg-red-50">
                  <TableHead>Ref Number</TableHead>
                  <TableHead>Candidate</TableHead>
                  <TableHead>Applied For</TableHead>
                  <TableHead>Rejection Reason</TableHead>
                  <TableHead>Date Rejected</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {applications.map((app) => (
                  <TableRow key={app.id}>
                    <TableCell className="font-mono text-xs">{app.reference_number}</TableCell>
                    <TableCell>
                        <div className="flex flex-col">
                            <span className="font-medium">{applicantName(app)}</span>
                            <span className="text-xs text-gray-500">{app.email}</span>
                        </div>
                    </TableCell>
                    <TableCell>
                        <Badge variant="outline">{app.jobs?.title}</Badge>
                    </TableCell>
                    <TableCell className="max-w-[200px] truncate text-gray-600" title={app.rejection_reason}>
                        {app.rejection_reason || 'N/A'}
                    </TableCell>
                    <TableCell className="text-sm">
                        {new Date(app.status_changed_at || app.updated_at).toLocaleDateString()}
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                        <Button variant="ghost" size="sm" onClick={() => setSelectedApp(app)}>
                            <Eye className="w-4 h-4 text-gray-600" />
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => openRejectionEmailModal(app)} title="Send Rejection Email">
                            <Mail className="w-4 h-4 text-red-500" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => handleRestore(app.id)} className="text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                            <RotateCcw className="w-4 h-4 mr-1" /> Restore
                        </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={rejectionModalOpen} onOpenChange={setRejectionModalOpen}>
          <DialogContent>
              <DialogHeader>
                  <DialogTitle>Send Rejection Email</DialogTitle>
              </DialogHeader>
              <div className="py-4 space-y-2">
                  <Label>Message/Reason</Label>
                  <Textarea 
                      value={rejectionReason} 
                      onChange={(e) => setRejectionReason(e.target.value)} 
                      rows={5}
                      placeholder="Reason for rejection..."
                  />
                  <p className="text-xs text-gray-500">This message will be included in the email sent to {rejectingApp?.email}.</p>
              </div>
              <DialogFooter>
                  <Button variant="outline" onClick={() => setRejectionModalOpen(false)}>Cancel</Button>
                  <Button onClick={handleSendRejectionEmail} disabled={sendingEmail} variant="destructive">
                      {sendingEmail ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Mail className="w-4 h-4 mr-2" />}
                      Send Email
                  </Button>
              </DialogFooter>
          </DialogContent>
      </Dialog>

      <ApplicationReviewModal 
        isOpen={!!selectedApp}
        onClose={() => setSelectedApp(null)}
        application={selectedApp}
        onSuccess={fetchApplications}
      />
    </div>
  );
};

export default AdminRejectedApplicationsPage;