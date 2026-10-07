import { supabase } from '@/lib/customSupabaseClient';
import { sendWhatsAppMessage } from './wasenderapiService';
import { WHATSAPP_PHONE } from '@/constants/branding';

const ADMIN_PHONE = WHATSAPP_PHONE;

/**
 * Sends a contact message via WhatsApp and logs it to the database
 */
export const sendContactMessageViaWhatsApp = async (formData) => {
    try {
        const { name, email, subject, message } = formData;
        
        // 1. Format the message for WhatsApp
        const whatsappTemplate = `*New Contact Form Submission*\n\n*Name:* ${name}\n*Email:* ${email}\n*Subject:* ${subject}\n\n*Message:*\n${message}`;

        // 2. Send via WhatsApp
        const waResult = await sendWhatsAppMessage(
            ADMIN_PHONE, 
            whatsappTemplate, 
            { name }, 
            null, 
            null
        );

        const status = waResult.success ? 'sent' : 'failed';

        // 3. Log to Database
        const { error: dbError } = await supabase
            .from('contact_messages')
            .insert([{
                name,
                email,
                subject,
                message,
                sent_via: 'whatsapp',
                status
            }]);

        if (dbError) {
            console.error("Error logging contact message to DB:", dbError);
            // We don't fail the whole process if DB logging fails, but we note it.
        }

        if (!waResult.success) {
            throw new Error(waResult.error || "Failed to send WhatsApp message");
        }

        return { success: true };
    } catch (error) {
        console.error("Error in sendContactMessageViaWhatsApp:", error);
        return { success: false, error: error.message };
    }
};

/**
 * Fetches all contact messages (Admin only)
 */
export const getContactMessages = async () => {
    try {
        const { data, error } = await supabase
            .from('contact_messages')
            .select('*')
            .order('created_at', { ascending: false });

        if (error) throw error;
        
        return { success: true, data };
    } catch (error) {
        console.error("Error fetching contact messages:", error);
        return { success: false, error: error.message };
    }
};

/**
 * Deletes a contact message (Admin only)
 */
export const deleteContactMessage = async (id) => {
    try {
        const { error } = await supabase
            .from('contact_messages')
            .delete()
            .eq('id', id);

        if (error) throw error;
        
        return { success: true };
    } catch (error) {
        console.error("Error deleting contact message:", error);
        return { success: false, error: error.message };
    }
};