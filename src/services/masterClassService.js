import { supabase } from '@/lib/customSupabaseClient';
import { sendWhatsAppMessage, sendWhatsAppMessageWithImage } from './wasenderapiService';
import { logWhatsAppMessage } from './whatsappLogService';
import { generateShareholderQRCode } from '@/utils/qrCodeGenerator';

/**
 * Saves a new registration to Supabase and sends WhatsApp confirmation/QR.
 */
export const saveMasterClassRegistration = async (formData) => {
  try {
    const isPayLater = formData.paymentMethod === 'pay_later';
    const initialStatus = isPayLater ? 'pending_payment' : 'completed';

    const payload = {
      full_name: formData.fullName,
      email: formData.email,
      phone: formData.phone,
      company_name: formData.companyName || null,
      module: formData.module,
      experience_level: formData.experienceLevel || 'Beginner',
      amount: 300,
      currency: 'USD',
      payment_method: formData.paymentMethod,
      payment_status: initialStatus,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };

    if (!payload.full_name || !payload.email || !payload.phone || !payload.module) {
        throw new Error("Missing required fields: Name, Email, Phone, or Module.");
    }

    const { data, error } = await supabase
      .from('master_class_registrations')
      .insert([payload])
      .select()
      .single();

    if (error) throw error;

    const qrData = {
        type: 'master_class_student',
        fullName: payload.full_name,
        email: payload.email,
        phone: payload.phone,
        companyName: payload.company_name,
        module: payload.module,
        registrationDate: payload.created_at,
        paymentStatus: payload.payment_status,
        paymentMethod: payload.payment_method,
        amount: payload.amount,
        transactionId: payload.payment_status === 'completed' ? `TX-${Date.now()}` : 'PENDING'
    };

    let msg = "";
    if (isPayLater) {
        msg = `Your Master Class registration is confirmed! Your QR code is attached. Payment is due before the class starts.\n\nModule: ${payload.module}`;
    } else {
        msg = `Your Master Class registration is confirmed and payment received! Your QR code is attached.\n\nModule: ${payload.module}\nAmount: $${payload.amount}`;
    }

    (async () => {
        try {
            const qrBase64 = await generateShareholderQRCode(qrData);
            const res = await sendWhatsAppMessageWithImage(payload.phone, msg, qrBase64);
            
            await logWhatsAppMessage({
                recipient_phone: payload.phone,
                message_type: 'master_class',
                status: res.success ? 'success' : 'failed',
                error_message: res.error,
                related_registration_id: data.id
            });
            
            if(res.success) {
                await supabase.from('master_class_registrations').update({ qr_code_sent: true, whatsapp_sent: true }).eq('id', data.id);
            }
        } catch (qrErr) {
            console.error("Failed to send QR WhatsApp:", qrErr);
        }
    })();

    return { data, error: null };
  } catch (error) {
    console.error('Error saving master class registration:', error.message);
    return { data: null, error };
  }
};

export const updatePaymentStatus = async (registrationId, status, transactionId) => {
  try {
    if (!registrationId) throw new Error("Registration ID is required for status update.");

    const { data, error } = await supabase
      .from('master_class_registrations')
      .update({ 
        payment_status: status, 
        transaction_id: transactionId,
        updated_at: new Date().toISOString()
      })
      .eq('id', registrationId)
      .select()
      .single();

    if (error) throw error;

    if (status === 'completed' || status === 'paid') {
       const msg = `Your Master Class payment has been confirmed. Thank you for registering!\n\nModule: ${data.module}`;
       
       sendWhatsAppMessage(data.phone, msg).then(async (res) => {
           await logWhatsAppMessage({
               recipient_phone: data.phone,
               message_type: 'master_class',
               status: res.success ? 'success' : 'failed',
               error_message: res.error,
               related_registration_id: data.id
           });
       });
    }

    return { data, error: null };
  } catch (error) {
    console.error(`Error updating payment status for ${registrationId}:`, error.message);
    return { data: null, error };
  }
};

export const getMasterClassRegistrations = async () => {
  try {
    const { data, error } = await supabase
      .from('master_class_registrations')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) throw error;
    return { data, error: null };
  } catch (error) {
    console.error('Error fetching registrations:', error.message);
    return { data: [], error };
  }
};