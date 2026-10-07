import { supabase } from '@/lib/customSupabaseClient';

export const getStatusHistory = async (applicationId) => {
  const { data, error } = await supabase
    .from('application_status_history')
    .select(`
      *,
      changer:changed_by (
        email,
        full_name
      )
    `)
    .eq('application_id', applicationId)
    .order('changed_at', { ascending: false });

  if (error) throw error;
  
  // Map changer info if available (profiles might not be joined if not setup perfectly, so fallback)
  return data.map(record => ({
    ...record,
    changed_by_name: record.changer?.full_name || record.changer?.email || 'System/Admin'
  }));
};

export const getApplicationsByStatus = async (status) => {
  const { data, error } = await supabase
    .from('applications')
    .select('*')
    .eq('status', status)
    .order('updated_at', { ascending: false });

  if (error) throw error;
  return data || [];
};

export const updateApplicationStatus = async (id, newStatus, extraData = {}, changedBy) => {
  // 1. Get current application to know old status
  const { data: currentApp, error: fetchError } = await supabase
    .from('applications')
    .select('status')
    .eq('id', id)
    .single();

  if (fetchError) throw fetchError;

  const oldStatus = currentApp.status;
  const now = new Date().toISOString();

  // 2. Prepare update payload
  const updatePayload = {
    status: newStatus,
    updated_at: now
  };

  if (changedBy) {
    updatePayload.status_changed_at = now;
    updatePayload.status_changed_by = changedBy;
  }

  // Add extra fields based on status
  if (newStatus === 'rejected') {
    updatePayload.rejection_reason = extraData.reason || null;
  } else if (newStatus === 'shortlisted') {
    updatePayload.interview_date = extraData.interviewDate || null;
  } else if (newStatus === 'pending') {
    // Reset fields if moving back to pending
    updatePayload.rejection_reason = null;
    updatePayload.interview_date = null;
  }

  // 3. Update application
  const { data: updatedApp, error: updateError } = await supabase
    .from('applications')
    .update(updatePayload)
    .eq('id', id)
    .select()
    .single();

  if (updateError) throw updateError;

  // 4. Log history
  let reasonText = 'Status updated';
  if (newStatus === 'rejected') reasonText = `Rejection: ${extraData.reason || 'No reason provided'}`;
  else if (newStatus === 'shortlisted') reasonText = extraData.interviewDate 
      ? `Shortlisted. Interview: ${new Date(extraData.interviewDate).toLocaleString()}` 
      : 'Shortlisted for review';
  else if (newStatus === 'pending') reasonText = extraData.reason || 'Restored to pending';

  const historyPayload = {
    application_id: id,
    old_status: oldStatus,
    new_status: newStatus,
    reason: reasonText,
    changed_by: changedBy,
    changed_at: now
  };

  const { error: historyError } = await supabase
    .from('application_status_history')
    .insert([historyPayload]);

  if (historyError) {
    console.error("Failed to log status history:", historyError);
  }

  return updatedApp;
};

export const getApplicationStats = async () => {
    const { data, error } = await supabase
        .from('applications')
        .select('status');
    
    if (error) throw error;
    
    const stats = {
        total: data.length,
        pending: 0,
        rejected: 0,
        shortlisted: 0
    };
    
    data.forEach(app => {
        const status = app.status || 'pending';
        if (stats[status] !== undefined) {
            stats[status]++;
        } else {
            stats.pending++; 
        }
    });
    
    return stats;
};