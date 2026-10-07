import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { updateApplicationStatus } from '@/services/applicationStatusService';
import { sendApplicationRejected, sendApplicationShortlisted } from '@/services/emailService';
import { WhatsAppService } from '@/services/WhatsAppService'; // Import WhatsApp Service
import { useAuth } from '@/context/AuthContext';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Download, User, Mail, Phone, Briefcase, Calendar, XCircle, CheckCircle, Clock, MessageSquare } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';

const ApplicationReviewModal = ({ isOpen, onClose, application, onSuccess }) => {
  const { user } = useAuth();
  const { toast } = useToast();
  const [loading, setLoading] = useState(false);
  const [action, setAction] = useState(null); // 'reject', 'shortlist'
  
  const [reason, setReason] = useState('');
  const [interviewDate, setInterviewDate] = useState('');
  const [sendWhatsApp, setSendWhatsApp] = useState(true); // Default to true

  if (!application) return null;

  const formatPhone = (phone) => {
    if (!phone) return 'N/A';
    const cleaned = phone.replace(/[^\d+]/g, '');
    if (cleaned.startsWith('+') && cleaned.length > 7) {
        return `${cleaned.slice(0, 4)} ${cleaned.slice(4, 7)} ${cleaned.slice(7)}`;
    }
    return cleaned;
  };

  const handleStatusUpdate = async (newStatus) => {
    setLoading(true);
    try {
        const extraData = {};
        if (newStatus === 'rejected') extraData.reason = reason;
        if (newStatus === 'shortlisted') extraData.interviewDate = interviewDate;

        await updateApplicationStatus(application.id, newStatus, extraData, user?.id);
        
        // Email Notifications
        if (newStatus === 'rejected') {
            await sendApplicationRejected(application.email, application, reason);
            toast({ title: "Email Sent", description: `Rejection email sent to ${application.candidate_name}` });
        } else if (newStatus === 'shortlisted') {
            const dateObj = new Date(interviewDate);
            const dateStr = dateObj.toLocaleDateString();
            const timeStr = dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            await sendApplicationShortlisted(application.email, application, {
                date: dateStr,
                time: timeStr,
                location: 'Details to follow'
            });
            toast({ title: "Email Sent", description: `Shortlist email sent to ${application.candidate_name}` });
        }

        // WhatsApp Notifications
        if (sendWhatsApp && application.phone) {
           let waMessage = '';
           if (newStatus === 'rejected') {
             waMessage = `Your application for ${application.jobs?.title || 'the position'} has been rejected. Thank you for your interest.`;
             await WhatsAppService.sendMessage(application.phone, waMessage, 'rejection', { 
                recipient_name: application.candidate_name,
                recipient_type: 'applicant' 
             });
             toast({ title: "WhatsApp Sent", description: "Rejection message sent." });
           } else if (newStatus === 'shortlisted') {
             waMessage = `Congratulations! Your application for ${application.jobs?.title || 'the position'} has been approved for the next stage. Please check your email for next steps.`;
             await WhatsAppService.sendMessage(application.phone, waMessage, 'approval', {
                recipient_name: application.candidate_name,
                recipient_type: 'applicant'
             });
             toast({ title: "WhatsApp Sent", description: "Approval message sent." });
           }
        }

        if (onSuccess) onSuccess();
        onClose();
        setAction(null);
        setReason('');
        setInterviewDate('');
    } catch (error) {
        console.error(error);
        toast({ title: "Error", description: "Failed to update status", variant: "destructive" });
    } finally {
        setLoading(false);
    }
  };

  const renderActionForm = () => {
      if (action === 'reject') {
          return (
              <div className="space-y-4 p-4 bg-red-50 rounded-lg border border-red-100 mt-4">
                  <h4 className="font-semibold text-red-800 flex items-center gap-2">
                      <XCircle className="w-4 h-4" /> Reject Application
                  </h4>
                  <div className="space-y-2">
                      <Label htmlFor="reason">Reason for Rejection</Label>
                      <Textarea 
                        id="reason" 
                        placeholder="e.g., Does not meet minimum experience requirements..." 
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        className="bg-white"
                        rows={3}
                      />
                  </div>
                  <div className="flex items-center space-x-2 py-2">
                     <Checkbox id="wa-reject" checked={sendWhatsApp} onCheckedChange={setSendWhatsApp} />
                     <Label htmlFor="wa-reject" className="flex items-center gap-2 cursor-pointer">
                        <MessageSquare className="w-4 h-4 text-green-600" /> Send WhatsApp Notification
                     </Label>
                  </div>
                  <div className="flex justify-end gap-2">
                      <Button variant="ghost" size="sm" onClick={() => setAction(null)}>Cancel</Button>
                      <Button 
                        variant="destructive" 
                        size="sm" 
                        onClick={() => handleStatusUpdate('rejected')}
                        disabled={loading || !reason.trim()}
                      >
                          {loading && <Loader2 className="w-3 h-3 mr-2 animate-spin" />} Confirm Rejection
                      </Button>
                  </div>
              </div>
          );
      }

      if (action === 'shortlist') {
        return (
            <div className="space-y-4 p-4 bg-green-50 rounded-lg border border-green-100 mt-4">
                <h4 className="font-semibold text-green-800 flex items-center gap-2">
                    <CheckCircle className="w-4 h-4" /> Shortlist / Approve Candidate
                </h4>
                <div className="space-y-2">
                    <Label htmlFor="interviewDate">Proposed Interview Date (Optional)</Label>
                    <Input 
                      id="interviewDate" 
                      type="datetime-local" 
                      value={interviewDate}
                      onChange={(e) => setInterviewDate(e.target.value)}
                      className="bg-white"
                    />
                </div>
                <div className="flex items-center space-x-2 py-2">
                     <Checkbox id="wa-approve" checked={sendWhatsApp} onCheckedChange={setSendWhatsApp} />
                     <Label htmlFor="wa-approve" className="flex items-center gap-2 cursor-pointer">
                        <MessageSquare className="w-4 h-4 text-green-600" /> Send WhatsApp Notification
                     </Label>
                  </div>
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" size="sm" onClick={() => setAction(null)}>Cancel</Button>
                    <Button 
                      className="bg-green-600 hover:bg-green-700 text-white" 
                      size="sm" 
                      onClick={() => handleStatusUpdate('shortlisted')}
                      disabled={loading}
                    >
                        {loading && <Loader2 className="w-3 h-3 mr-2 animate-spin" />} Confirm Shortlist
                    </Button>
                </div>
            </div>
        );
      }

      const currentStatus = application.status || 'pending';

      return (
          <div className="flex gap-3 pt-4 border-t mt-4">
              <Button 
                variant="outline" 
                className="flex-1 border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                onClick={() => setAction('reject')}
                disabled={currentStatus === 'rejected'}
              >
                  <XCircle className="w-4 h-4 mr-2" /> Reject
              </Button>
              <Button 
                variant="outline" 
                className="flex-1 border-green-200 text-green-600 hover:bg-green-50 hover:text-green-700"
                onClick={() => setAction('shortlist')}
                disabled={currentStatus === 'shortlisted'}
              >
                  <CheckCircle className="w-4 h-4 mr-2" /> Shortlist
              </Button>
              {currentStatus !== 'pending' && (
                  <Button 
                    variant="ghost"
                    className="flex-1"
                    onClick={() => handleStatusUpdate('pending')}
                  >
                     <Clock className="w-4 h-4 mr-2" /> Reset
                  </Button>
              )}
          </div>
      );
  };

  const status = application.status || 'pending';

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex justify-between items-center pr-8">
            <span className="text-[#003D82]">Review Application</span>
            <Badge variant={
                status === 'pending' ? 'outline' : 
                status === 'rejected' ? 'destructive' : 'default'
            } className={status === 'shortlisted' ? 'bg-green-600' : ''}>
                {status.toUpperCase()}
            </Badge>
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-6 py-2">
            {/* Candidate Info */}
            <div className="grid grid-cols-2 gap-4 text-sm">
                <div className="space-y-3">
                    <div className="flex items-center gap-2 text-gray-600">
                        <User className="w-4 h-4" /> 
                        <span className="font-semibold text-gray-900">{application.candidate_name}</span>
                    </div>
                    <div className="flex items-center gap-2 text-gray-600">
                        <Mail className="w-4 h-4" /> 
                        <a href={`mailto:${application.email}`} className="text-blue-600 hover:underline">{application.email}</a>
                    </div>
                    <div className="flex items-center gap-2 text-gray-600">
                        <Phone className="w-4 h-4" /> 
                        <span>{formatPhone(application.phone)}</span>
                    </div>
                </div>
                <div className="space-y-3">
                     <div className="flex items-center gap-2 text-gray-600">
                        <Briefcase className="w-4 h-4" /> 
                        <span className="font-semibold text-gray-900">{application.jobs?.title}</span>
                    </div>
                    <div className="flex items-center gap-2 text-gray-600">
                        <Calendar className="w-4 h-4" /> 
                        <span>Applied: {new Date(application.created_at).toLocaleDateString()}</span>
                    </div>
                    <div className="flex items-center gap-2 text-gray-600">
                        <span className="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded">REF: {application.reference_number}</span>
                    </div>
                </div>
            </div>

            {/* Description */}
            <div className="bg-gray-50 p-4 rounded-lg text-sm text-gray-700 border">
                <h5 className="font-semibold mb-2 text-gray-900">Cover Note</h5>
                <p className="whitespace-pre-wrap">{application.description || "No description provided."}</p>
            </div>

            {/* Status Details if exists */}
            {status === 'rejected' && application.rejection_reason && (
                 <div className="bg-red-50 p-4 rounded-lg text-sm text-red-700 border border-red-100">
                    <h5 className="font-semibold mb-1 flex items-center gap-2"><XCircle className="w-4 h-4"/> Rejection Reason</h5>
                    <p>{application.rejection_reason}</p>
                 </div>
            )}
            
            {status === 'shortlisted' && application.interview_date && (
                 <div className="bg-green-50 p-4 rounded-lg text-sm text-green-700 border border-green-100">
                    <h5 className="font-semibold mb-1 flex items-center gap-2"><Calendar className="w-4 h-4"/> Scheduled Interview</h5>
                    <p>{new Date(application.interview_date).toLocaleString()}</p>
                 </div>
            )}

            {/* CV Download */}
            {application.cv_url && (
                <div className="flex justify-start">
                    <a href={application.cv_url} target="_blank" rel="noopener noreferrer">
                        <Button variant="outline" size="sm" className="gap-2">
                            <Download className="w-4 h-4" /> Download CV
                        </Button>
                    </a>
                </div>
            )}

            {/* Action Area */}
            {renderActionForm()}

        </div>
      </DialogContent>
    </Dialog>
  );
};

export default ApplicationReviewModal;