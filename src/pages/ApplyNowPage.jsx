import React, { useState, useEffect } from 'react';
import { getAllActiveJobs } from '@/services/jobsService';
import PublicJobCard from '@/components/PublicJobCard';
import JobApplicationForm from '@/components/JobApplicationForm';
import ApplicationConfirmationModal from '@/components/ApplicationConfirmationModal';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Loader2, Search, Briefcase, RefreshCcw } from 'lucide-react';
import { motion } from 'framer-motion';

const ApplyNowPage = () => {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  
  // Modal States
  const [selectedJob, setSelectedJob] = useState(null);
  const [isApplicationOpen, setIsApplicationOpen] = useState(false);
  const [isSuccessOpen, setIsSuccessOpen] = useState(false);
  const [successData, setSuccessData] = useState(null);

  const fetchJobs = async () => {
    setLoading(true);
    try {
      const data = await getAllActiveJobs();
      setJobs(data || []);
    } catch (error) {
      console.error("Failed to load active jobs", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchJobs();
  }, []);

  const handleApplyClick = (job) => {
    setSelectedJob(job);
    setIsApplicationOpen(true);
  };

  const handleApplicationSuccess = (data) => {
    setSuccessData(data);
    setIsSuccessOpen(true);
    fetchJobs(); 
  };

  const filteredJobs = jobs.filter(job => {
      if (!searchQuery.trim()) return true;
      const query = searchQuery.toLowerCase();
      const matchesTitle = job.title?.toLowerCase().includes(query);
      const matchesDept = (job.department || job.type || '').toLowerCase().includes(query);
      const matchesLoc = (job.location || '').toLowerCase().includes(query);
      return matchesTitle || matchesDept || matchesLoc;
  });

  return (
    <div className="min-h-screen bg-gray-50 pb-20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-[#003D82] via-[#004e9a] to-[#002855] text-white py-20 px-4 relative overflow-hidden">
        <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div className="max-w-7xl mx-auto text-center relative z-10">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
            >
                <h1 className="text-4xl md:text-6xl font-extrabold mb-6 tracking-tight">
                    Build Your Future With Us
                </h1>
                <p className="text-xl text-blue-100 max-w-2xl mx-auto font-light leading-relaxed">
                    Join a team of innovators, creators, and problem solvers. 
                    Explore our open positions and find where you belong.
                </p>
            </motion.div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20">
        
        {/* Search Bar */}
        <div className="bg-white rounded-xl shadow-xl p-6 mb-10 border border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div className="relative w-full max-w-2xl">
                <Search className="absolute left-4 top-3.5 h-5 w-5 text-gray-400" />
                <Input 
                    placeholder="Search by job title, department, or location..." 
                    className="pl-12 py-6 text-lg bg-gray-50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-[#D4AF37]/20 transition-all"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                />
            </div>
            
            <div className="flex gap-2 w-full md:w-auto">
                <Button variant="outline" onClick={fetchJobs} disabled={loading} className="gap-2 h-12 px-6">
                   <RefreshCcw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                   Refresh
                </Button>
            </div>
        </div>

        {/* Content Area */}
        {loading ? (
             <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {[1, 2, 3].map(i => (
                    <div key={i} className="h-64 bg-white rounded-xl shadow-sm animate-pulse border border-gray-100 p-6 space-y-4">
                        <div className="h-6 bg-gray-200 rounded w-3/4"></div>
                        <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                        <div className="space-y-2 pt-4">
                            <div className="h-3 bg-gray-100 rounded w-full"></div>
                            <div className="h-3 bg-gray-100 rounded w-full"></div>
                        </div>
                    </div>
                ))}
             </div>
        ) : filteredJobs.length === 0 ? (
             <div className="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
                <Briefcase className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h3 className="text-2xl font-bold text-gray-700 mb-2">No positions available</h3>
                <p className="text-gray-500 max-w-md mx-auto">
                    {searchQuery ? "We couldn't find any jobs matching your search." : "There are currently no open positions. Please check back later!"}
                </p>
                {searchQuery && (
                    <Button variant="link" onClick={() => setSearchQuery('')} className="mt-4 text-[#003D82]">
                        Clear Search
                    </Button>
                )}
             </div>
        ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {filteredJobs.map((job, idx) => (
                    <motion.div
                        key={job.id}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.3, delay: idx * 0.1 }}
                        className="h-full"
                    >
                        <PublicJobCard job={job} onApply={handleApplyClick} />
                    </motion.div>
                ))}
            </div>
        )}
      </div>

      {/* Modals */}
      <JobApplicationForm 
        isOpen={isApplicationOpen}
        onClose={() => setIsApplicationOpen(false)}
        job={selectedJob}
        onSuccess={handleApplicationSuccess}
      />

      <ApplicationConfirmationModal 
        isOpen={isSuccessOpen} 
        onClose={() => setIsSuccessOpen(false)}
        applicationData={successData}
      />
    </div>
  );
};

export default ApplyNowPage;