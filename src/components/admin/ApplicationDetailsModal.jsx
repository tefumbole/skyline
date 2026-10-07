import React from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Download, Mail, Phone, Calendar, Briefcase, FileText, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

const ApplicationDetailsModal = ({ isOpen, onClose, application }) => {
  if (!application) return null;

  const formatPhone = (phone) => {
    if (!phone) return 'N/A';
    const cleaned = phone.replace(/[^\d+]/g, '');
    if (cleaned.startsWith('+') && cleaned.length > 7) {
        return `${cleaned.slice(0, 4)} ${cleaned.slice(4, 7)} ${cleaned.slice(7)}`;
    }
    return cleaned;
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle className="flex justify-between items-center pr-8">
            <span className="text-[#003D82]">Application Details</span>
            <Badge variant={application.status === 'pending' ? 'outline' : 'secondary'}>
                {application.status.toUpperCase()}
            </Badge>
          </DialogTitle>
        </DialogHeader>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
            {/* Candidate Info */}
            <div className="space-y-4">
                <h3 className="font-bold text-lg flex items-center gap-2 border-b pb-2">
                    <User className="w-5 h-5 text-[#D4AF37]" /> Candidate
                </h3>
                <div className="space-y-2 text-sm">
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Name:</span> {application.candidate_name}
                    </p>
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Email:</span> 
                        <a href={`mailto:${application.email}`} className="text-blue-600 hover:underline flex items-center gap-1">
                           <Mail className="w-3 h-3" /> {application.email}
                        </a>
                    </p>
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Phone:</span> 
                        <a href={`tel:${application.phone}`} className="text-blue-600 hover:underline flex items-center gap-1">
                           <Phone className="w-3 h-3" /> {formatPhone(application.phone)}
                        </a>
                    </p>
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Ref #:</span> 
                        <code className="bg-gray-100 px-2 py-0.5 rounded text-xs">{application.reference_number}</code>
                    </p>
                </div>
            </div>

            {/* Job Info */}
            <div className="space-y-4">
                <h3 className="font-bold text-lg flex items-center gap-2 border-b pb-2">
                    <Briefcase className="w-5 h-5 text-[#D4AF37]" /> Position
                </h3>
                <div className="space-y-2 text-sm">
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Job Title:</span> 
                        {application.jobs?.title || 'Unknown Position'}
                    </p>
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Date:</span> 
                        <span className="flex items-center gap-1">
                            <Calendar className="w-3 h-3 text-gray-400" />
                            {new Date(application.created_at).toLocaleDateString()}
                        </span>
                    </p>
                    <p className="flex items-center gap-2">
                        <span className="font-semibold w-20">Time:</span> 
                        {new Date(application.created_at).toLocaleTimeString()}
                    </p>
                </div>
            </div>
        </div>

        {/* Description / Cover Letter */}
        <div className="space-y-2 mt-2">
            <h3 className="font-bold text-sm text-gray-500 uppercase flex items-center gap-2">
                <FileText className="w-4 h-4" /> Cover Note / Description
            </h3>
            <div className="p-4 bg-gray-50 rounded-lg border text-sm text-gray-700 min-h-[100px]">
                {application.description || "No description provided."}
            </div>
        </div>

        {/* CV Action */}
        <div className="flex justify-end pt-4 border-t mt-4 gap-4">
            {application.cv_url ? (
                <a href={application.cv_url} target="_blank" rel="noopener noreferrer">
                    <Button className="bg-[#003D82] text-white hover:bg-[#002d62]">
                        <Download className="w-4 h-4 mr-2" /> Download CV (PDF)
                    </Button>
                </a>
            ) : (
                <Button variant="outline" disabled>No CV Attached</Button>
            )}
            <Button variant="outline" onClick={onClose}>Close</Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default ApplicationDetailsModal;