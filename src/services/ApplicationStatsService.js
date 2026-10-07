import { supabase } from '@/lib/customSupabaseClient';
import { formatDistanceToNow } from 'date-fns';

export const getJobApplicationStats = async (jobId) => {
  try {
    // Fetch job to get expected_applicants
    const { data: job, error: jobError } = await supabase
      .from('jobs')
      .select('expected_applicants')
      .eq('id', jobId)
      .single();

    if (jobError) throw jobError;

    const { count, error } = await supabase
      .from('applications')
      .select('*', { count: 'exact', head: true })
      .eq('job_id', jobId);

    if (error) throw error;

    const { data: latestApp, error: latestError } = await supabase
      .from('applications')
      .select('created_at')
      .eq('job_id', jobId)
      .order('created_at', { ascending: false })
      .limit(1)
      .maybeSingle();

    let lastSubmission = 'No applications yet';
    if (latestApp && latestApp.created_at) {
        lastSubmission = formatDistanceToNow(new Date(latestApp.created_at), { addSuffix: true });
    }

    const current = count || 0;
    const expected = job.expected_applicants || 50;

    return {
      current,
      expected,
      lastSubmission,
      // Supporting legacy format if needed
      count: current,
      total_applicants: current,
      expected_applicants: expected
    };
  } catch (error) {
    console.error("Error fetching stats:", error);
    return { current: 0, expected: 50, count: 0, lastSubmission: 'N/A' };
  }
};