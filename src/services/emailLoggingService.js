import { supabase } from '@/lib/customSupabaseClient';

export const logEmailSent = async (applicationId, emailType, recipientEmail, status, messageId = null, errorMessage = null) => {
  try {
    const { error } = await supabase.from('email_logs').insert({
      application_id: applicationId,
      email_type: emailType,
      recipient_email: recipientEmail,
      status: status,
      message_id: messageId,
      error_message: errorMessage,
      sent_at: new Date().toISOString()
    });

    if (error) {
      console.error('Failed to log email:', error);
    }
  } catch (err) {
    console.error('Exception in email logging:', err);
  }
};

export const getEmailHistory = async (applicationId) => {
  const { data, error } = await supabase
    .from('email_logs')
    .select('*')
    .eq('application_id', applicationId)
    .order('sent_at', { ascending: false });

  if (error) throw error;
  return data;
};

export const updateEmailStatus = async (logId, status, error = null) => {
  const { error: dbError } = await supabase
    .from('email_logs')
    .update({ 
      status, 
      error_message: error 
    })
    .eq('id', logId);
    
  if (dbError) throw dbError;
};