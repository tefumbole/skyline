import React from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { X, Mail, Phone, Building, GraduationCap, CreditCard, Calendar, RefreshCw } from 'lucide-react';
import { updatePaymentStatus } from '@/services/masterClassService';
import { useToast } from '@/components/ui/use-toast';

const StudentDetailsModal = ({ student, isOpen, onClose, onUpdate }) => {
  const { toast } = useToast();

  if (!student) return null;

  const handleStatusUpdate = async (newStatus) => {
    const { error } = await updatePaymentStatus(student.id, newStatus, student.transaction_id);
    if (error) {
      toast({
        title: "Error updating status",
        description: error.message,
        variant: "destructive"
      });
    } else {
      toast({
        title: "Status Updated",
        description: `Payment status changed to ${newStatus}`,
      });
      onUpdate(); // Trigger refresh in parent
    }
  };

  const getStatusColor = (status) => {
    switch(status) {
      case 'paid': return 'bg-green-100 text-green-800 border-green-200';
      case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'failed': return 'bg-red-100 text-red-800 border-red-200';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <Dialog.Root open={isOpen} onOpenChange={onClose}>
      <Dialog.Portal>
        <Dialog.Overlay className="fixed inset-0 bg-black/50 z-50 animate-in fade-in" />
        <Dialog.Content className="fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-white p-6 shadow-lg duration-200 sm:rounded-lg animate-in fade-in zoom-in-95 slide-in-from-left-1/2 slide-in-from-top-[48%]">
          
          <div className="flex flex-col space-y-1.5 text-center sm:text-left">
            <div className="flex justify-between items-start">
              <Dialog.Title className="text-lg font-semibold leading-none tracking-tight text-[#003366]">
                Student Details
              </Dialog.Title>
              <Dialog.Close asChild>
                <button className="rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground">
                  <X className="h-4 w-4" />
                  <span className="sr-only">Close</span>
                </button>
              </Dialog.Close>
            </div>
            <Dialog.Description className="text-sm text-muted-foreground">
              Registration ID: {student.id}
            </Dialog.Description>
          </div>

          <div className="grid gap-4 py-4">
            {/* Header Info */}
            <div className="flex items-center gap-4 pb-4 border-b">
              <div className="h-12 w-12 rounded-full bg-[#003366] text-white flex items-center justify-center text-xl font-bold">
                {student.full_name?.charAt(0)}
              </div>
              <div>
                <h3 className="font-bold text-lg">{student.full_name}</h3>
                <div className={`text-xs px-2 py-0.5 rounded-full inline-block border ${getStatusColor(student.payment_status)}`}>
                  {student.payment_status?.toUpperCase()}
                </div>
              </div>
            </div>

            {/* Contact Details */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-500 flex items-center gap-1">
                  <Mail className="w-3 h-3" /> Email
                </label>
                <div className="text-sm break-all">{student.email}</div>
              </div>
              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-500 flex items-center gap-1">
                  <Phone className="w-3 h-3" /> Phone
                </label>
                <div className="text-sm">{student.phone}</div>
              </div>
              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-500 flex items-center gap-1">
                  <Building className="w-3 h-3" /> Company
                </label>
                <div className="text-sm">{student.company_name || 'N/A'}</div>
              </div>
              <div className="space-y-1">
                <label className="text-xs font-semibold text-gray-500 flex items-center gap-1">
                  <GraduationCap className="w-3 h-3" /> Experience
                </label>
                <div className="text-sm">{student.experience_level || 'N/A'}</div>
              </div>
            </div>

            {/* Module Info */}
            <div className="bg-gray-50 p-3 rounded-md border">
               <label className="text-xs font-semibold text-gray-500">Registered Module</label>
               <div className="font-medium text-[#003366]">{student.module}</div>
            </div>

            {/* Payment Details */}
            <div className="space-y-3 pt-2">
              <h4 className="text-sm font-semibold border-b pb-1">Payment Information</h4>
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-gray-500 block text-xs">Method</span>
                  <div className="flex items-center gap-1">
                    <CreditCard className="w-3 h-3" />
                    {student.payment_method === 'mobile_money' ? 'Mobile Money' : 'Card'}
                  </div>
                </div>
                <div>
                  <span className="text-gray-500 block text-xs">Amount</span>
                  <span className="font-mono">${student.amount}</span>
                </div>
                <div className="col-span-2">
                  <span className="text-gray-500 block text-xs">Transaction ID</span>
                  <span className="font-mono text-xs">{student.transaction_id || 'Pending...'}</span>
                </div>
                <div className="col-span-2">
                  <span className="text-gray-500 block text-xs">Registration Date</span>
                  <span className="text-xs flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {new Date(student.created_at).toLocaleString()}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="flex flex-col sm:flex-row gap-2 justify-end pt-4 border-t">
            {student.payment_status === 'pending' && (
              <Button 
                onClick={() => handleStatusUpdate('paid')}
                className="bg-green-600 hover:bg-green-700 text-white h-9"
              >
                Mark as Paid
              </Button>
            )}
            {student.payment_status !== 'failed' && (
              <Button 
                variant="destructive"
                onClick={() => handleStatusUpdate('failed')}
                className="h-9"
              >
                Mark as Failed
              </Button>
            )}
            {student.payment_status !== 'pending' && (
               <Button 
               variant="outline"
               onClick={() => handleStatusUpdate('pending')}
               className="h-9"
             >
               <RefreshCw className="w-3 h-3 mr-2" />
               Reset to Pending
             </Button>
            )}
             <Button variant="secondary" onClick={onClose} className="h-9">
              Close
            </Button>
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
};

export default StudentDetailsModal;