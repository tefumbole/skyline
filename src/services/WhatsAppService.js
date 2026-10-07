import { supabase } from '@/lib/customSupabaseClient';
import { sendWhatsAppMessage } from './wasenderapiService';

/**
 * WhatsApp Service for handling all WhatsApp communications
 * Reuses existing WaSender API integration
 */
export const WhatsAppService = {
  /**
   * Validates phone number format
   * @param {string} phone 
   * @returns {boolean}
   */
  isValidPhone(phone) {
    // Basic E.164 validation or local format check
    // Expecting +250... or similar
    const phoneRegex = /^\+?[0-9]{10,15}$/;
    return phoneRegex.test(phone);
  },

  /**
   * Sends a WhatsApp message and logs it
   * @param {string} phone - Recipient phone number
   * @param {string} message - Message content
   * @param {string} messageType - Type of message (rejection, approval, etc.)
   * @param {object} metadata - Optional: recipient_name, recipient_type
   * @returns {Promise<{success: boolean, error?: string, messageId?: string}>}
   */
  async sendMessage(phone, message, messageType, metadata = {}) {
    try {
      if (!this.isValidPhone(phone)) {
        throw new Error(`Invalid phone number format: ${phone}`);
      }

      // 1. Log as pending
      const { data: logEntry, error: logError } = await supabase
        .from('whatsapp_message_logs')
        .insert([{
          phone_number: phone,
          message_type: messageType,
          message_content: message,
          recipient_name: metadata.recipient_name || null,
          recipient_type: metadata.recipient_type || null,
          status: 'pending'
        }])
        .select()
        .single();

      if (logError) {
        console.error('Failed to create log entry:', logError);
        // Continue trying to send even if logging fails initially, or return?
        // Let's continue but warn.
      }

      const logId = logEntry?.id;

      // 2. Send via WaSender API
      const result = await sendWhatsAppMessage(phone, message);

      // 3. Update log status
      if (logId) {
        await supabase
          .from('whatsapp_message_logs')
          .update({
            status: result.success ? 'sent' : 'failed',
            error_message: result.error || null,
            updated_at: new Date().toISOString()
          })
          .eq('id', logId);
      }

      return result;

    } catch (error) {
      console.error('WhatsAppService Error:', error);
      
      // Attempt to log failure if not already logged
      try {
        await supabase.from('whatsapp_message_logs').insert([{
          phone_number: phone,
          message_type: messageType,
          message_content: message,
          status: 'failed',
          error_message: error.message,
          recipient_name: metadata.recipient_name || null
        }]);
      } catch (e) { /* Ignore logging error */ }

      return {
        success: false,
        error: error.message
      };
    }
  },

  /**
   * Batch send messages
   * @param {Array<{phone, message, name}>} recipients 
   * @param {string} messageType 
   * @returns {Promise<{total: number, sent: number, failed: number}>}
   */
  async sendBatchMessage(recipients, messageType) {
    let sent = 0;
    let failed = 0;

    // Process in sequence to avoid rate limits or overwhelm
    for (const recipient of recipients) {
      const result = await this.sendMessage(
        recipient.phone, 
        recipient.message, 
        messageType, 
        { recipient_name: recipient.name, recipient_type: recipient.type }
      );
      
      if (result.success) sent++;
      else failed++;
    }

    return { total: recipients.length, sent, failed };
  }
};