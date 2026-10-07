import { supabase } from '@/lib/customSupabaseClient';
import { WhatsAppService } from './WhatsAppService';

export const checkAndSendDeadlineReminders = async () => {
  try {
    // 1. Find jobs with deadlines tomorrow (between now and 24 hours from now)
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    
    const { data: jobs, error: jobsError } = await supabase
      .from('jobs')
      .select('id, title, deadline')
      .gt('deadline', now.toISOString())
      .lt('deadline', tomorrow.toISOString())
      .eq('status', 'open');

    if (jobsError) throw jobsError;
    if (!jobs || jobs.length === 0) return { sent: 0, message: 'No upcoming deadlines found.' };

    let totalSent = 0;

    // 2. For each job, find applicants
    for (const job of jobs) {
      const { data: applications, error: appsError } = await supabase
        .from('applications')
        .select('id, phone, full_name')
        .eq('job_id', job.id);

      if (appsError) continue;

      for (const applicant of applications) {
        if (!applicant.phone) continue;

        // Check if we already sent a reminder to this person for this job recently
        // using the logs table to avoid schema changes on applications table
        const { data: existingLog } = await supabase
          .from('whatsapp_message_logs')
          .select('id')
          .eq('phone_number', applicant.phone)
          .eq('message_type', 'deadline_reminder')
          .ilike('message_content', `%${job.title}%`)
          .gte('created_at', new Date(now.getTime() - 24 * 60 * 60 * 1000).toISOString()) // sent in last 24h
          .single();

        if (existingLog) continue; // Already sent

        const message = `Reminder: Application deadline for ${job.title} is tomorrow. Good luck!`;
        
        await WhatsAppService.sendMessage(
          applicant.phone,
          message,
          'deadline_reminder',
          { recipient_name: applicant.full_name, recipient_type: 'applicant' }
        );
        
        totalSent++;
      }
    }

    return { sent: totalSent, message: `Sent ${totalSent} deadline reminders.` };

  } catch (error) {
    console.error('DeadlineReminderService Error:', error);
    return { sent: 0, error: error.message };
  }
};