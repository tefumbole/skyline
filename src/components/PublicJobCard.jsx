import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MapPin, DollarSign, ArrowRight, Eye, Users, Clock, Briefcase } from 'lucide-react';
import { formatSalary } from '@/services/currencyService';
import { getJobApplicationStats } from '@/services/jobsService';
import JobCountdownTimer from '@/components/JobCountdownTimer';
import JobDetailsModal from '@/components/JobDetailsModal';
import { cn } from '@/lib/utils';
import { isPast, parseISO } from 'date-fns';

const PublicJobCard = ({ job, onApply }) => {
  const [stats, setStats] = useState({ 
    total_applicants: 0, 
    expected_applicants: 50, 
    last_application_date: 'N/A'
  });
  const [isDetailsOpen, setIsDetailsOpen] = useState(false);

  useEffect(() => {
    if (job?.id) {
        getJobApplicationStats(job.id).then(setStats);
    }
  }, [job]);

  const getEmploymentTypeBadge = (type) => {
    const t = type || 'Full-Time';
    if (t === 'Full-Time') return <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-200 border-blue-200 shadow-sm">{t}</Badge>;
    if (t === 'Part-Time') return <Badge className="bg-green-100 text-green-700 hover:bg-green-200 border-green-200 shadow-sm">{t}</Badge>;
    if (t === 'Contract') return <Badge className="bg-orange-100 text-orange-700 hover:bg-orange-200 border-orange-200 shadow-sm">{t}</Badge>;
    return <Badge variant="secondary">{t}</Badge>;
  };

  const isExpired = job.deadline && isPast(parseISO(job.deadline));

  return (
    <>
      <Card className="h-full hover:shadow-xl transition-all duration-300 border-t-4 border-t-[#D4AF37] group flex flex-col overflow-hidden relative rounded-xl bg-white shadow-lg">
        <CardContent className="p-6 flex flex-col h-full pt-6 relative">
          
          <div className="flex justify-between items-start mb-4">
             <div className="flex flex-wrap gap-2 items-center">
                <Badge variant="secondary" className="bg-gray-100 text-gray-700 font-medium capitalize border border-gray-200">
                    {job.department || 'Engineering'}
                </Badge>
                {getEmploymentTypeBadge(job.employment_type)}
             </div>
          </div>

          {/* Prominent Countdown Timer */}
          {job.enable_countdown && job.deadline && (
             <div className="mb-4">
                <JobCountdownTimer deadline={job.deadline} />
             </div>
          )}
  
          <h3 className="text-xl font-bold text-[#003D82] mb-3 group-hover:text-[#0056b3] transition-colors line-clamp-2 min-h-[3.5rem]">
              {job.title}
          </h3>
  
          <div className="space-y-4 mb-6 flex-1">
              <div className="flex items-center text-gray-600 text-sm">
                  <MapPin className="w-4 h-4 mr-2 text-[#D4AF37] shrink-0" />
                  <span className="truncate">{job.location || 'Remote'}</span>
              </div>
              
              {job.salary && (
                  <div className="flex items-center text-gray-700 font-medium text-sm">
                      <DollarSign className="w-4 h-4 mr-2 text-[#D4AF37] shrink-0" />
                      <span>{formatSalary(job.salary, 'RWF')}</span>
                  </div>
              )}
              
              <div className="bg-gray-50 rounded-lg p-3 space-y-2 border border-gray-100">
                 <div className="flex justify-between items-center text-xs text-gray-700">
                    <span className="flex items-center gap-1.5" title="Total Positions Available">
                        <Briefcase className="w-3.5 h-3.5 text-blue-600" /> 
                        <span className="font-semibold">{job.max_positions || 1} Positions Available</span>
                    </span>
                 </div>
                 <div className="flex justify-between items-center text-xs text-gray-700">
                    <span className="flex items-center gap-1.5" title="Number of Applicants">
                        <Users className="w-3.5 h-3.5 text-blue-600" /> 
                        <span>{stats.total_applicants}/{job.max_applicants || '∞'} Applicants</span>
                    </span>
                 </div>
                 <div className="flex justify-between items-center text-xs text-gray-700 border-t border-gray-200 pt-2 mt-1">
                    <span className="text-gray-400 italic flex items-center gap-1" title="Last Application">
                       <Clock className="w-3 h-3" />
                       Last submission: {stats.last_application_date}
                    </span>
                 </div>
              </div>
          </div>
  
          <div className="mt-auto pt-4 border-t border-gray-100 flex gap-3">
               <Button 
                  onClick={() => setIsDetailsOpen(true)}
                  variant="outline"
                  className="flex-1 hover:bg-gray-50 text-gray-700"
              >
                  <Eye className="w-4 h-4 mr-2" /> View Details
              </Button>
              <Button 
                  onClick={() => onApply(job)}
                  disabled={isExpired}
                  className={cn(
                    "flex-1 text-white font-semibold shadow-md hover:shadow-lg transition-all group/btn",
                    isExpired ? "bg-gray-400 hover:bg-gray-400 cursor-not-allowed" : "bg-[#003D82] hover:bg-[#002d62]"
                  )}
              >
                  {isExpired ? 'Closed' : 'Apply Now'}
                  {!isExpired && <ArrowRight className="w-4 h-4 ml-2 transition-transform group-hover/btn:translate-x-1" />}
              </Button>
          </div>
        </CardContent>
      </Card>

      <JobDetailsModal 
        isOpen={isDetailsOpen}
        onClose={() => setIsDetailsOpen(false)}
        job={job}
        onApply={(j) => { setIsDetailsOpen(false); onApply(j); }}
      />
    </>
  );
};

export default PublicJobCard;