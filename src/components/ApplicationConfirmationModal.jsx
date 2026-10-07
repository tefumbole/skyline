import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { CheckCircle2, Briefcase } from 'lucide-react';

const ApplicationConfirmationModal = ({ isOpen, onClose, applicationData }) => {
  if (!applicationData) return null;

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="sm:max-w-md text-center">
        <DialogHeader>
          <div className="mx-auto w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-4">
            <CheckCircle2 className="w-8 h-8 text-green-600" />
          </div>
          <DialogTitle className="text-xl font-bold text-[#003D82] text-center">
            Application Submitted!
          </DialogTitle>
        </DialogHeader>

        <div className="py-4 space-y-4">
          <p className="text-gray-600">
            Thank you, <span className="font-semibold text-gray-900">{applicationData.candidateName}</span>! 
            Your application has been successfully received.
          </p>

          <div className="bg-gray-50 p-4 rounded-lg border border-gray-100 text-left">
            <div className="mb-2">
                <span className="text-xs font-bold text-gray-500 uppercase tracking-wide">Position</span>
                <div className="flex items-center gap-2 font-medium text-[#003D82]">
                    <Briefcase className="w-4 h-4" />
                    {applicationData.jobTitle}
                </div>
            </div>
            {applicationData.referenceNumber && (
                <div>
                    <span className="text-xs font-bold text-gray-500 uppercase tracking-wide">Reference Number</span>
                    <p className="font-mono text-sm bg-white border border-gray-200 p-1 px-2 rounded w-fit mt-1">
                        {applicationData.referenceNumber}
                    </p>
                </div>
            )}
          </div>

          <p className="text-xs text-gray-400">
            We've sent a confirmation email to {applicationData.email}. 
            Our team will review your application and contact you shortly.
          </p>
        </div>

        <DialogFooter className="sm:justify-center">
          <Button 
            onClick={onClose} 
            className="bg-[#003D82] hover:bg-[#002855] min-w-[150px]"
          >
            Back to Jobs
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default ApplicationConfirmationModal;