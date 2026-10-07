import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { updateRegistration, deleteRegistration } from '@/services/registrationsService';
import { sendRegistrationConfirmation } from '@/services/emailService';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Trash2, Mail, CheckCircle, Clock, XCircle, DollarSign } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
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
} from "@/components/ui/alert-dialog";

const RegistrationDetailsModal = ({ registration, open, onClose, onUpdate }) => {
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(registration?.status || 'pending');
  const [sendingEmail, setSendingEmail] = useState(false);
  const { toast } = useToast();

  if (!registration) return null;

  const handleStatusChange = async (newStatus) => {
    setStatus(newStatus);
    setLoading(true);
    try {
      await updateRegistration(registration.id, { status: newStatus });
      toast({ title: "Status Updated", description: `Registration marked as ${newStatus}.` });
      onUpdate();
    } catch (error) {
      toast({ title: "Error", description: "Failed to update status.", variant: "destructive" });
      setStatus(registration.status); // Revert
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    setLoading(true);
    try {
      await deleteRegistration(registration.id);
      toast({ title: "Deleted", description: "Registration removed permanently." });
      onUpdate();
      onClose();
    } catch (error) {
      toast({ title: "Error", description: "Failed to delete registration.", variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  const handleResendEmail = async () => {
    setSendingEmail(true);
    try {
      // Mock fetching course names if needed, or pass them
      const emailData = {
         client_name: registration.client_name,
         total_price: registration.total_price,
         status: registration.status,
         course_names: registration.course_ids // Simplified: assumes we handle IDs in template or lookup elsewhere
      };
      
      await sendRegistrationConfirmation(emailData, registration.client_email);
      toast({ title: "Email Sent", description: "Confirmation email resent to client." });
    } catch (error) {
      toast({ title: "Email Error", description: "Could not send email.", variant: "destructive" });
    } finally {
      setSendingEmail(false);
    }
  };

  const getStatusColor = (s) => {
    switch(s) {
      case 'confirmed': return 'bg-green-100 text-green-800';
      case 'completed': return 'bg-blue-100 text-blue-800';
      case 'cancelled': return 'bg-red-100 text-red-800';
      default: return 'bg-yellow-100 text-yellow-800';
    }
  };

  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle className="text-xl text-[#003D82] flex items-center justify-between">
             <span>Registration Details</span>
             <Badge className={getStatusColor(status)}>{status.toUpperCase()}</Badge>
          </DialogTitle>
        </DialogHeader>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
          <div className="space-y-4">
            <h3 className="font-semibold text-gray-500 border-b pb-2">Client Information</h3>
            <div className="grid grid-cols-[100px_1fr] gap-2 text-sm">
                <span className="text-gray-500">Name:</span>
                <span className="font-medium">{registration.client_name}</span>
                
                <span className="text-gray-500">Email:</span>
                <span className="font-medium truncate">{registration.client_email}</span>
                
                <span className="text-gray-500">Phone:</span>
                <span className="font-medium">{registration.client_phone || 'N/A'}</span>
                
                <span className="text-gray-500">Company:</span>
                <span className="font-medium">{registration.company_name || 'N/A'}</span>
            </div>
          </div>

          <div className="space-y-4">
            <h3 className="font-semibold text-gray-500 border-b pb-2">Order Summary</h3>
            <div className="space-y-2 text-sm">
                <div className="flex justify-between items-center">
                    <span className="text-gray-500">Date:</span>
                    <span>{new Date(registration.created_at).toLocaleDateString()}</span>
                </div>
                <div className="flex justify-between items-center">
                    <span className="text-gray-500">Courses:</span>
                    <span className="font-medium">{Array.isArray(registration.course_ids) ? registration.course_ids.length : 0} Selected</span>
                </div>
                 <div className="flex justify-between items-center">
                    <span className="text-gray-500">Payment:</span>
                    <span className={`capitalize font-medium ${registration.payment_status === 'paid' ? 'text-green-600' : 'text-orange-600'}`}>
                        {registration.payment_status || 'Pending'}
                    </span>
                </div>
                <div className="mt-4 p-3 bg-gray-50 rounded flex justify-between items-center border border-gray-100">
                    <span className="font-bold text-gray-700">Total Price:</span>
                    <span className="font-bold text-xl text-[#003D82]">${Number(registration.total_price).toLocaleString()}</span>
                </div>
            </div>
          </div>
        </div>

        <div className="bg-gray-50 p-4 rounded-lg space-y-3">
             <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-gray-700">Update Status</span>
                <Select value={status} onValueChange={handleStatusChange} disabled={loading}>
                    <SelectTrigger className="w-[180px] bg-white">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="confirmed">Confirmed</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                    </SelectContent>
                </Select>
             </div>
        </div>

        <DialogFooter className="flex sm:justify-between items-center gap-2 mt-4">
           <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="destructive" size="sm" disabled={loading} className="mr-auto">
                    <Trash2 className="w-4 h-4 mr-2" /> Delete
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Delete Registration?</AlertDialogTitle>
                  <AlertDialogDescription>This action cannot be undone. This will permanently remove the registration record.</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Cancel</AlertDialogCancel>
                  <AlertDialogAction onClick={handleDelete} className="bg-red-600">Delete</AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
           </AlertDialog>

           <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={handleResendEmail} disabled={sendingEmail}>
                  {sendingEmail ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Mail className="w-4 h-4 mr-2" />} 
                  Resend Email
              </Button>
              <Button onClick={onClose}>Close</Button>
           </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default RegistrationDetailsModal;