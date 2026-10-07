import { supabase } from '@/lib/customSupabaseClient';

export const getAllApplications = async () => {
  const { data, error } = await supabase
    .from('applications')
    .select(`
      *,
      jobs (
        title
      )
    `)
    .order('created_at', { ascending: false });
  if (error) throw error;
  return data;
};

export const getApplicationById = async (id) => {
  const { data, error } = await supabase
    .from('applications')
    .select(`
      *,
      jobs (
        title,
        salary,
        description
      )
    `)
    .eq('id', id)
    .single();
  if (error) throw error;
  return data;
};

export const getApplicationByReference = async (referenceNumber) => {
  const { data, error } = await supabase
    .from('applications')
    .select(`
      *,
      jobs (
        title,
        description
      )
    `)
    .eq('reference_number', referenceNumber)
    .single();
  if (error) throw error;
  return data;
};

export const createApplication = async (appData) => {
  const { data, error } = await supabase
    .from('applications')
    .insert([{
        ...appData,
        expected_salary: appData.expected_salary,
        availability: appData.availability,
        availability_days: appData.availability_days
    }])
    .select()
    .single();
  if (error) throw error;
  return data;
};

export const updateApplicationStatus = async (id, status, reason = null) => {
  const user = await supabase.auth.getUser();
  const userId = user.data.user?.id;

  // First get current status for history
  const { data: currentApp } = await supabase
    .from('applications')
    .select('status')
    .eq('id', id)
    .single();

  // Update application
  const { data, error } = await supabase
    .from('applications')
    .update({ 
      status,
      status_changed_at: new Date().toISOString(),
      status_changed_by: userId,
      ...(reason && { rejection_reason: reason })
    })
    .eq('id', id)
    .select()
    .single();
  
  if (error) throw error;

  // Record history
  if (currentApp && currentApp.status !== status) {
    await supabase.from('application_status_history').insert({
      application_id: id,
      previous_status: currentApp.status,
      new_status: status,
      changed_by: userId,
      reason: reason
    });
  }

  return data;
};

export const getApplicationsByJob = async (jobId) => {
  const { data, error } = await supabase
    .from('applications')
    .select('*')
    .eq('job_id', jobId)
    .order('created_at', { ascending: false });
  if (error) throw error;
  return data;
};

export const checkExistingApplication = async (jobId, email) => {
  const { data, error } = await supabase
    .from('applications')
    .select('id')
    .eq('job_id', jobId)
    .eq('email', email)
    .maybeSingle();
  
  if (error) throw error;
  return !!data; // Returns true if exists, false otherwise
};

/**
 * Delete a single application
 * @param {string} applicationId 
 * @returns {Promise<{success: boolean, error: any}>}
 */
export const deleteJobApplication = async (applicationId) => {
  try {
    const { error } = await supabase
      .from('applications')
      .delete()
      .eq('id', applicationId);

    if (error) throw error;
    return { success: true, error: null };
  } catch (error) {
    console.error(`Error deleting application ${applicationId}:`, error);
    return { success: false, error };
  }
};

/**
 * Delete multiple applications
 * @param {string[]} applicationIds 
 * @returns {Promise<{success: boolean, error: any}>}
 */
export const deleteMultipleJobApplications = async (applicationIds) => {
  try {
    if (!applicationIds || applicationIds.length === 0) {
      return { success: true, error: null };
    }

    const { error } = await supabase
      .from('applications')
      .delete()
      .in('id', applicationIds);

    if (error) throw error;
    return { success: true, error: null };
  } catch (error) {
    console.error('Error deleting multiple applications:', error);
    return { success: false, error };
  }
};