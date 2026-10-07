import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Briefcase, MapPin, DollarSign, Users, CalendarCheck, Clock } from 'lucide-react';
import { formatSalary } from '@/services/currencyService';
import JobCountdownTimer from '@/components/JobCountdownTimer';

const JobDetailsModal = ({ isOpen, onClose, job, onApply }) => {
  if (!job) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl max-h-[90vh] p-0 overflow-hidden flex flex-col">
        <DialogHeader className="p-6 pb-2">
            <div className="flex justify-between items-start">
                <div className="space-y-2">
                    <div className="flex gap-2">
                        <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                            {job.department || 'Engineering'}
                        </Badge>
                        <Badge variant="secondary" className="bg-gray-100 text-gray-800 border-gray-200">
                            {job.employment_type || 'Full-Time'}
                        </Badge>
                    </div>
                    <DialogTitle className="text-2xl font-bold text-[#003D82]">{job.title}</DialogTitle>
                </div>
                {job.enable_countdown && job.deadline && (
                    <JobCountdownTimer deadline={job.deadline} />
                )}
            </div>
            
            <div className="flex flex-wrap gap-4 text-sm text-gray-600 mt-2">
                <div className="flex items-center gap-1">
                    <MapPin className="w-4 h-4 text-[#D4AF37]" /> {job.location || 'Remote'}
                </div>
                <div className="flex items-center gap-1">
                    <DollarSign className="w-4 h-4 text-[#D4AF37]" /> {job.salary ? formatSalary(job.salary, 'RWF') : 'Competitive'}
                </div>
                <div className="flex items-center gap-1">
                    <Users className="w-4 h-4 text-[#D4AF37]" /> {job.current_applicants || 0}/{job.max_applicants || '∞'} Applicants
                </div>
                <div className="flex items-center gap-1">
                     <Clock className="w-4 h-4 text-[#D4AF37]" /> {job.employment_type || 'Full-Time'}
                </div>
                <div className="flex items-center gap-1">
                     <Briefcase className="w-4 h-4 text-[#D4AF37]" />
                     <span className="font-semibold text-green-600">{job.max_positions || 1} Positions Available</span>
                </div>
            </div>
        </DialogHeader>

        <div className="flex-1 overflow-y-auto p-6 pt-0">
            <div className="space-y-6">
                <div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">About the Role</h3>
                    <p className="text-gray-600 leading-relaxed whitespace-pre-wrap">{job.description}</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {job.responsibilities && (
                        <div className="bg-gray-50 p-4 rounded-lg">
                            <h4 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                <Briefcase className="w-4 h-4 text-blue-600" /> Responsibilities
                            </h4>
                            <p className="text-gray-600 text-sm whitespace-pre-wrap">{job.responsibilities}</p>
                        </div>
                    )}
                    
                    {job.requirements && (
                        <div className="bg-gray-50 p-4 rounded-lg">
                            <h4 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                <CalendarCheck className="w-4 h-4 text-blue-600" /> Requirements
                            </h4>
                            <p className="text-gray-600 text-sm whitespace-pre-wrap">{job.requirements}</p>
                        </div>
                    )}
                </div>

                 {job.qualifications && (
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">Qualifications</h3>
                        <p className="text-gray-600 leading-relaxed whitespace-pre-wrap">{job.qualifications}</p>
                    </div>
                )}
            </div>
        </div>

        <DialogFooter className="p-6 pt-4 border-t bg-gray-50">
            <Button variant="outline" onClick={onClose}>Close</Button>
            <Button onClick={() => onApply(job)} className="bg-[#003D82] hover:bg-[#002d62]">Apply Now</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default JobDetailsModal;