import { supabase } from '@/lib/customSupabaseClient';
import { generatePDFsForMessage } from './pdfGenerationService';

const BUCKET_NAME = 'system-assets';

export const generateMessageReference = async () => {
  try {
    const { data, error } = await supabase.rpc('generate_message_reference');
    if (error) throw error;
    return data;
  } catch (error) {
    console.error('Error generating message reference:', error);
    // Fallback if RPC fails
    return `MSG-${Date.now()}`;
  }
};

export const uploadMessageAttachment = async (file) => {
  if (!file) throw new Error('No file provided');

  // Validate size (10MB max)
  const maxSize = 10 * 1024 * 1024;
  if (file.size > maxSize) {
    throw new Error('File too large. Maximum size is 10MB.');
  }

  const fileExt = file.name.split('.').pop();
  const fileName = `attachment-${Date.now()}.${fileExt}`;
  const filePath = `messaging/attachments/${fileName}`;

  try {
    const { error: uploadError } = await supabase.storage
      .from(BUCKET_NAME)
      .upload(filePath, file, { upsert: true });

    if (uploadError) throw uploadError;

    const { data: { publicUrl } } = supabase.storage
      .from(BUCKET_NAME)
      .getPublicUrl(filePath);

    return {
      name: file.name,
      url: publicUrl,
      size: file.size,
      type: file.type
    };
  } catch (error) {
    console.error('Error uploading attachment:', error);
    throw error;
  }
};

export const deleteMessageAttachment = async (fileUrl) => {
  if (!fileUrl) return true;
  
  try {
    const urlParts = fileUrl.split('/');
    const bucketIndex = urlParts.findIndex(part => part === BUCKET_NAME);
    if (bucketIndex === -1) return true;
    
    const filePath = urlParts.slice(bucketIndex + 1).join('/');
    
    const { error } = await supabase.storage
      .from(BUCKET_NAME)
      .remove([filePath]);

    if (error) throw error;
    return true;
  } catch (error) {
    console.error('Error deleting attachment:', error);
    throw error;
  }
};

export const createMessageWithRecipients = async (messageData, recipients, attachments) => {
  try {
    const { data: user } = await supabase.auth.getUser();
    const userId = user?.user?.id;

    // 1. Generate Reference
    const reference = await generateMessageReference();

    // 2. Create Message Record
    const { data: message, error: messageError } = await supabase
      .from('messages')
      .insert({
        reference,
        category: messageData.category,
        subject: messageData.subject,
        body: messageData.body,
        status: messageData.isScheduled ? 'scheduled' : 'sending',
        send_email: messageData.sendEmail,
        send_whatsapp: messageData.sendWhatsapp,
        generate_pdf: messageData.generatePdf,
        is_scheduled: messageData.isScheduled,
        scheduled_for: messageData.scheduledFor || null,
        created_by: userId
      })
      .select()
      .single();

    if (messageError) throw messageError;

    // 3. Insert Attachments
    if (attachments && attachments.length > 0) {
      const attachmentRecords = attachments.map(att => ({
        message_id: message.id,
        file_name: att.name,
        file_url: att.url,
        file_size: att.size,
        mime_type: att.type,
        type: att.type.includes('pdf') ? 'pdf' : att.type.includes('image') ? 'image' : 'other'
      }));

      const { error: attError } = await supabase
        .from('message_attachments')
        .insert(attachmentRecords);
      
      if (attError) console.error('Error inserting attachments:', attError);
    }

    // 4. Insert Recipients
    let insertedRecipientsList = [];
    if (recipients && recipients.length > 0) {
      const recipientRecords = recipients.map((r, index) => {
        const randomRef = Math.random().toString(36).substring(2, 10).toUpperCase();
        const refCode = `${reference}-${randomRef}`;
        const appUrl = window.location.origin; // Using origin for absolute URL if needed, but relative is fine too
        
        return {
          message_id: message.id,
          recipient_type: r.type || 'other',
          recipient_id: r.id || null,
          recipient_name: r.name || 'Unknown',
          recipient_email: r.email || null,
          recipient_phone: r.phone || null,
          reference_code: refCode,
          verification_url: `${appUrl}/verify/${refCode}`,
          barcode_value: refCode,
          status: 'pending'
        };
      });

      const { data: insertedRecipients, error: recError } = await supabase
        .from('message_recipients')
        .insert(recipientRecords)
        .select();

      if (recError) throw recError;
      insertedRecipientsList = insertedRecipients;

      // 5. Create Queue Jobs
      if (!messageData.isScheduled && insertedRecipients) {
        const queueJobs = [];
        
        insertedRecipients.forEach(rec => {
          if (messageData.sendEmail && rec.recipient_email) {
            queueJobs.push({
              message_id: message.id,
              message_recipient_id: rec.id,
              channel: 'email',
              status: 'pending'
            });
          }
          if (messageData.sendWhatsapp && rec.recipient_phone) {
            queueJobs.push({
              message_id: message.id,
              message_recipient_id: rec.id,
              channel: 'whatsapp',
              status: 'pending'
            });
          }
        });

        if (queueJobs.length > 0) {
          const { error: queueError } = await supabase
            .from('message_queue')
            .insert(queueJobs);
            
          if (queueError) console.error('Error queueing jobs:', queueError);
        }
      }

      // 6. Log Creation
      await supabase.from('message_logs').insert({
        message_id: message.id,
        channel: 'system',
        event_type: 'created',
        details: { 
          recipients_count: recipients.length, 
          channels: {
            email: messageData.sendEmail,
            whatsapp: messageData.sendWhatsapp
          } 
        }
      });
    }

    return { message, recipients: insertedRecipientsList };
  } catch (error) {
    console.error('Error creating message:', error);
    throw error;
  }
};

export const createMessageWithRecipientsAndPDFs = async (messageData, recipients, attachments) => {
  // First, create the core message and queue jobs
  const result = await createMessageWithRecipients(messageData, recipients, attachments);
  
  if (!result || !result.message) throw new Error("Failed to create message");

  let pdfStats = null;

  // If PDF generation is requested, handle it now
  if (messageData.generatePdf && result.recipients && result.recipients.length > 0) {
    pdfStats = await generatePDFsForMessage(result.message.id, messageData, result.recipients);
    
    // Log PDF generation completion
    await supabase.from('message_logs').insert({
      message_id: result.message.id,
      channel: 'system',
      event_type: 'pdf_generation_completed',
      details: { 
        total_recipients: pdfStats.total,
        successful_pdfs: pdfStats.successCount,
        failed_pdfs: pdfStats.failCount
      }
    });
  }

  return { ...result, pdfStats };
};