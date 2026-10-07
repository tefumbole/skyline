import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { useAuth } from '@/context/AuthContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Loader2, Search, Eye } from 'lucide-react';
import { format } from 'date-fns';
import { useToast } from '@/components/ui/use-toast';

const AllApplicationsPage = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  
  const [allApplications, setAllApplications] = useState([]);
  const [filteredApplications, setFilteredApplications] = useState([]);
  const [jobs, setJobs] = useState([]);
  
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedJob, setSelectedJob] = useState('all');
  const [selectedStatus, setSelectedStatus] = useState('all');

  // 1. Check if user?.id exists before fetching
  useEffect(() => {
    if (user?.id) {
      fetchData();
    } else {
      setLoading(false);
    }
  }, [user?.id]);

  const fetchData = async () => {
    setLoading(true);
    setError(null);
    try {
      // 2. Fetch applications with proper Supabase query
      const { data: appData, error: appError } = await supabase
        .from('applications')
        .select(`
          id,
          job_id,
          full_name,
          email,
          phone,
          status,
          created_at,
          updated_at,
          job:jobs(id, title, type)
        `)
        .order('created_at', { ascending: false });

      if (appError) throw appError;

      console.log('Applications fetched:', appData);
      
      // Store ALL applications in state
      setAllApplications(appData || []);
      
      // Fetch jobs to populate the job filter dropdown
      const { data: jobData, error: jobError } = await supabase
        .from('jobs')
        .select('id, title')
        .order('created_at', { ascending: false });
        
      if (jobError) throw jobError;
      setJobs(jobData || []);

    } catch (err) {
      console.error('Error fetching data:', err);
      setError(err.message || 'Failed to load applications');
    } finally {
      setLoading(false);
    }
  };

  // Apply filters whenever filter state or source data changes
  useEffect(() => {
    applyFilters(allApplications);
  }, [searchQuery, selectedJob, selectedStatus, allApplications]);

  const applyFilters = (apps) => {
    let filtered = [...apps];

    // Filter by Job ID
    if (selectedJob !== 'all') {
      filtered = filtered.filter(app => app.job_id === selectedJob);
    }

    // Filter by Status
    if (selectedStatus !== 'all') {
      filtered = filtered.filter(app => (app.status || '').toLowerCase() === selectedStatus.toLowerCase());
    }

    // Filter by Search Query (Name, Email, or Phone)
    if (searchQuery.trim() !== '') {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(app => 
        (app.full_name && app.full_name.toLowerCase().includes(query)) ||
        (app.email && app.email.toLowerCase().includes(query)) ||
        (app.phone && app.phone.toLowerCase().includes(query))
      );
    }

    console.log('Filtered applications:', filtered);
    setFilteredApplications(filtered);
  };

  const handleViewApplication = () => {
    toast({
      title: "View Application",
      description: "🚧 This feature isn't implemented yet—but don't worry! You can request it in your next prompt! 🚀",
    });
  };

  const getStatusBadge = (status) => {
    const normalized = (status || 'pending').toLowerCase();
    switch (normalized) {
      case 'pending': return <Badge className="bg-yellow-100 text-yellow-800 hover:bg-yellow-200">Pending</Badge>;
      case 'shortlisted': return <Badge className="bg-green-100 text-green-800 hover:bg-green-200">Shortlisted</Badge>;
      case 'rejected': return <Badge className="bg-red-100 text-red-800 hover:bg-red-200">Rejected</Badge>;
      case 'interview': return <Badge className="bg-blue-100 text-blue-800 hover:bg-blue-200">Interview</Badge>;
      default: return <Badge variant="outline" className="capitalize">{status || 'Pending'}</Badge>;
    }
  };

  if (!user?.id && !loading) {
    return (
      <div className="p-8 flex flex-col items-center justify-center min-h-[400px]">
        <p className="text-gray-500">Please log in to view applications.</p>
      </div>
    );
  }

  return (
    <div className="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-[#003D82]">All Applications</h1>
          <p className="text-gray-500 mt-1">Review and manage all candidate applications across all jobs.</p>
        </div>
        <div className="bg-blue-50 text-[#003D82] px-4 py-2 rounded-lg font-medium text-sm border border-blue-100">
          Total Applicants: {allApplications.length}
        </div>
      </div>

      <Card className="border-gray-200 shadow-sm">
        <CardHeader className="pb-4 border-b border-gray-100">
          <CardTitle className="text-lg font-semibold text-gray-800">Filter & Search</CardTitle>
        </CardHeader>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Search Filter */}
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
              <Input 
                placeholder="Search name, email, phone..." 
                className="pl-9 bg-white"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            
            {/* Job Filter Dropdown */}
            <Select value={selectedJob} onValueChange={setSelectedJob}>
              <SelectTrigger className="bg-white">
                <SelectValue placeholder="All Jobs" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Jobs</SelectItem>
                {jobs.map(job => (
                  <SelectItem key={job.id} value={job.id}>{job.title}</SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Status Filter Dropdown */}
            <Select value={selectedStatus} onValueChange={setSelectedStatus}>
              <SelectTrigger className="bg-white">
                <SelectValue placeholder="All Statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="shortlisted">Shortlisted</SelectItem>
                <SelectItem value="interview">Interview</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <Card className="border-gray-200 shadow-sm overflow-hidden">
        <CardContent className="p-0">
          {loading ? (
            <div className="flex flex-col items-center justify-center py-20 text-gray-500">
              <Loader2 className="w-8 h-8 animate-spin mb-4 text-[#003D82]" />
              <p>Loading applications...</p>
            </div>
          ) : error ? (
            <div className="flex flex-col items-center justify-center py-20 text-red-500 bg-red-50">
              <p className="font-medium">{error}</p>
              <Button variant="outline" className="mt-4 border-red-200 text-red-600 hover:bg-red-100" onClick={fetchData}>
                Try Again
              </Button>
            </div>
          ) : filteredApplications.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-24 text-center px-4 bg-gray-50">
              <div className="w-16 h-16 bg-white border border-gray-200 rounded-full flex items-center justify-center mb-4 shadow-sm">
                <Search className="w-8 h-8 text-gray-400" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">No applications found</h3>
              <p className="text-gray-500 max-w-md">
                We couldn't find any applications matching your current search and filter criteria.
              </p>
              {(searchQuery || selectedJob !== 'all' || selectedStatus !== 'all') && (
                <Button 
                  variant="outline" 
                  className="mt-6 border-[#003D82] text-[#003D82] hover:bg-blue-50"
                  onClick={() => {
                    setSearchQuery('');
                    setSelectedJob('all');
                    setSelectedStatus('all');
                  }}
                >
                  Clear All Filters
                </Button>
              )}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader className="bg-gray-50 border-b border-gray-200">
                  <TableRow>
                    <TableHead className="font-semibold text-gray-700">Applicant</TableHead>
                    <TableHead className="font-semibold text-gray-700">Contact Info</TableHead>
                    <TableHead className="font-semibold text-gray-700">Job Applied For</TableHead>
                    <TableHead className="font-semibold text-gray-700">Status</TableHead>
                    <TableHead className="font-semibold text-gray-700">Applied Date</TableHead>
                    <TableHead className="text-right font-semibold text-gray-700">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredApplications.map(app => (
                    <TableRow key={app.id} className="hover:bg-gray-50/80 border-b border-gray-100 transition-colors">
                      <TableCell className="font-medium text-gray-900">
                        {app.full_name || 'Unknown'}
                      </TableCell>
                      <TableCell>
                        <div className="text-sm text-gray-800">{app.email || 'N/A'}</div>
                        <div className="text-xs text-gray-500 mt-1">{app.phone || 'No phone'}</div>
                      </TableCell>
                      <TableCell className="text-sm text-gray-700 font-medium">
                        {app.job?.title || app.jobs?.title || 'Unknown Job'}
                      </TableCell>
                      <TableCell>
                        {getStatusBadge(app.status)}
                      </TableCell>
                      <TableCell className="text-sm text-gray-600">
                        {app.created_at ? format(new Date(app.created_at), 'MMM d, yyyy') : 'N/A'}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button 
                          variant="ghost" 
                          size="sm" 
                          className="text-[#003D82] hover:bg-blue-50 hover:text-blue-800"
                          onClick={() => handleViewApplication(app.id)}
                        >
                          <Eye className="w-4 h-4 mr-2" /> View
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default AllApplicationsPage;