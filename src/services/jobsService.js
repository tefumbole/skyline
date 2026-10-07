import { supabase } from '@/lib/customSupabaseClient';
import { formatDistanceToNow } from 'date-fns';

export const getAllActiveJobs = async () => {
  const { data, error } = await supabase
    .from('jobs')
    .select('id, title, description, location, type, salary, created_at, status, min_requirements, requirements, qualifications, responsibilities, deadline, max_positions, expected_applicants, employment_type, enable_countdown, current_applicants')
    .eq('status', 'active') 
    .order('created_at', { ascending: false });

  if (error) {
    console.error("Error fetching active jobs:", error);
    throw error;
  }
  return data;
};

export const getAllJobs = async () => {
  const { data, error } = await supabase
    .from('jobs')
    .select('*')
    .order('created_at', { ascending: false });
  if (error) throw error;
  return data;
};

export const getJobById = async (id) => {
  const { data, error } = await supabase
    .from('jobs')
    .select('*')
    .eq('id', id)
    .single();
  if (error) throw error;
  return data;
};

export const getJobWithDetails = async (id) => {
    return getJobById(id);
};

export const getAllJobsWithDetails = async () => {
    return getAllJobs();
};

export const createJob = async (jobData) => {
  const { data, error } = await supabase
    .from('jobs')
    .insert([{
      title: jobData.title,
      description: jobData.description,
      salary: jobData.salary,
      min_requirements: jobData.min_requirements,
      requirements: jobData.requirements,
      qualifications: jobData.qualifications,
      responsibilities: jobData.responsibilities,
      deadline: jobData.deadline,
      max_positions: jobData.max_positions,
      expected_applicants: jobData.expected_applicants,
      employment_type: jobData.employment_type,
      status: jobData.status || 'open',
      enable_countdown: jobData.enable_countdown !== undefined ? jobData.enable_countdown : true
    }])
    .select()
    .single();
  if (error) throw error;
  return data;
};

export const updateJob = async (id, updates) => {
  const { data, error } = await supabase
    .from('jobs')
    .update({ 
      ...updates, 
      updated_at: new Date().toISOString() 
    })
    .eq('id', id)
    .select()
    .single();
  if (error) throw error;
  return data;
};

export const updateJobDeadline = async (id, deadline) => {
    return updateJob(id, { deadline });
};

export const deleteJob = async (id) => {
  const { error } = await supabase
    .from('jobs')
    .delete()
    .eq('id', id);
  if (error) throw error;
  return true;
};

export const getJobStats = async () => {
  const { data, error } = await supabase
    .from('jobs')
    .select('id, status, current_applicants');
  
  if (error) throw error;

  return {
    total: data.length,
    open: data.filter(j => j.status === 'open').length,
    closed: data.filter(j => j.status === 'closed').length,
    totalApplicants: data.reduce((sum, j) => sum + (j.current_applicants || 0), 0)
  };
};

export const incrementApplicantCount = async (jobId) => {
  const { error } = await supabase.rpc('increment_job_applicants', { row_id: jobId });

  if (error) {
    console.warn("RPC increment failed, falling back to manual update", error);
    const { data: job, error: fetchError } = await supabase
      .from('jobs')
      .select('current_applicants')
      .eq('id', jobId)
      .single();
      
    if (fetchError) return;

    const newCount = (job.current_applicants || 0) + 1;

    await supabase
      .from('jobs')
      .update({ current_applicants: newCount })
      .eq('id', jobId);
  }
};

export const isJobDeadlinePassed = (deadline) => {
    if (!deadline) return false;
    return new Date(deadline) < new Date();
};

export const getTimeUntilDeadline = (deadline) => {
    if (!deadline) return null;
    const now = new Date();
    const end = new Date(deadline);
    const diff = end - now;
    if (diff <= 0) return { expired: true };
    return {
        expired: false,
        days: Math.floor(diff / (1000 * 60 * 60 * 24)),
        hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((diff / 1000 / 60) % 60)
    };
};

export const checkJobAvailability = async (jobId) => {
    const { data: job, error } = await supabase
        .from('jobs')
        .select('status, deadline, current_applicants, max_positions, title, enable_countdown')
        .eq('id', jobId)
        .single();
    
    if (error || !job) {
        return { available: false, reason: "Job not found" };
    }

    if (job.status !== 'open' && job.status !== 'active') {
        return { available: false, reason: "Job is currently closed" };
    }

    if (job.deadline && isJobDeadlinePassed(job.deadline)) {
        return { available: false, reason: "Application deadline has passed" };
    }
    
    return { available: true, job };
};

export const getJobApplicationStats = async (jobId) => {
    try {
        // Fetch job details for expected_applicants
        const { data: job, error: jobError } = await supabase
            .from('jobs')
            .select('expected_applicants, current_applicants')
            .eq('id', jobId)
            .single();
        
        if (jobError || !job) {
            return { total_applicants: 0, expected_applicants: 50, last_application_date: 'N/A', available_spots: 0 };
        }

        // Count actual applications
        const { count, error: countError } = await supabase
            .from('applications')
            .select('*', { count: 'exact', head: true })
            .eq('job_id', jobId);

        // Get latest application
        const { data: latestApp, error: latestError } = await supabase
            .from('applications')
            .select('created_at')
            .eq('job_id', jobId)
            .order('created_at', { ascending: false })
            .limit(1)
            .maybeSingle();
        
        let lastApplicationDate = 'No applications yet';
        if (latestApp && latestApp.created_at) {
            lastApplicationDate = formatDistanceToNow(new Date(latestApp.created_at), { addSuffix: true });
        }

        // Use the count from applications table as it's the source of truth
        const actualCount = count || 0;
        
        // Calculate remaining spots based on EXPECTED applicants (e.g. countdown to close)
        // Default expected_applicants to 50 if null
        const expected = job.expected_applicants || 50;
        const remainingSpots = Math.max(0, expected - actualCount);

        return {
            total_applicants: actualCount,
            expected_applicants: expected,
            last_application_date: lastApplicationDate,
            available_spots: remainingSpots
        };
    } catch (e) {
        console.error("Error fetching application stats", e);
        return { total_applicants: 0, expected_applicants: 50, last_application_date: 'N/A', available_spots: 0 };
    }
};

// Keeping for backward compatibility if used elsewhere, aliasing to new function
export const getApplicationStats = getJobApplicationStats;