import { sendWhatsAppMessage } from '@/services/wasenderapiService';
import { formatPhoneNumber } from '@/utils/phoneNumberFormatter';

// Mock Service for Notification Management
const NOTIFICATIONS_KEY = 'ab_notifications';
const NOTIFICATION_RECIPIENTS_KEY = 'ab_notification_recipients';

const getLocalNotifications = () => JSON.parse(localStorage.getItem(NOTIFICATIONS_KEY) || '[]');
const saveLocalNotifications = (data) => localStorage.setItem(NOTIFICATIONS_KEY, JSON.stringify(data));

const getLocalRecipients = () => JSON.parse(localStorage.getItem(NOTIFICATION_RECIPIENTS_KEY) || '[]');
const saveLocalRecipients = (data) => localStorage.setItem(NOTIFICATION_RECIPIENTS_KEY, JSON.stringify(data));

export const createNotification = async (notificationData, recipients, sendViaWhatsApp = false) => {
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const notifications = getLocalNotifications();
    const notificationId = `notif-${Date.now()}`;
    
    const newNotification = {
        id: notificationId,
        ...notificationData,
        sent_at: new Date().toISOString(),
        is_archived: false
    };
    
    notifications.push(newNotification);
    saveLocalNotifications(notifications);

    // Create recipient records
    const allRecipients = getLocalRecipients();
    const newRecipients = recipients.map(r => ({
        id: `nr-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        notification_id: notificationId,
        recipient_id: r.id,
        recipient_type: r.type, 
        recipient_name: r.name, 
        is_read: false,
        created_at: new Date().toISOString()
    }));
    
    saveLocalRecipients([...allRecipients, ...newRecipients]);
    
    const results = [];

    if (sendViaWhatsApp) {
        for (const recipient of recipients) {
            const formattedPhone = formatPhoneNumber(recipient.phone);
            const messageText = notificationData.message.replace('{name}', recipient.name || 'User');
            
            if (!formattedPhone) {
                console.warn(`[NotificationService] Invalid phone for ${recipient.name}`);
                results.push({
                    recipientName: recipient.name,
                    phone: recipient.phone,
                    success: false,
                    error: "Invalid or missing phone number"
                });
                continue;
            }

            console.log("[NotificationService] Sending WhatsApp payload:", {
                to: formattedPhone,
                text: messageText
            });

            try {
                const waResult = await sendWhatsAppMessage(formattedPhone, messageText);
                results.push({
                    recipientName: recipient.name,
                    phone: formattedPhone,
                    success: waResult.success,
                    error: waResult.error || null
                });
            } catch (error) {
                console.error(`[NotificationService] Exception sending to ${formattedPhone}:`, error);
                results.push({
                    recipientName: recipient.name,
                    phone: formattedPhone,
                    success: false,
                    error: error.message || "Failed to send message"
                });
            }
        }
    }
    
    return { notification: newNotification, results };
};

export const getNotificationHistory = async () => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const notifications = getLocalNotifications();
    const recipients = getLocalRecipients();
    
    return notifications.map(n => {
        const recs = recipients.filter(r => r.notification_id === n.id);
        return {
            ...n,
            recipient_count: recs.length,
            read_count: recs.filter(r => r.is_read).length
        };
    }).sort((a,b) => new Date(b.sent_at) - new Date(a.sent_at));
};

export const getNotificationsByRecipient = async (recipientId) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const recipients = getLocalRecipients().filter(r => r.recipient_id === recipientId);
    const notifications = getLocalNotifications();
    
    return recipients.map(r => {
        const n = notifications.find(notif => notif.id === r.notification_id);
        return { ...n, ...r }; 
    }).filter(n => n.id); 
};

export const markAsRead = async (recipientRecordId) => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const all = getLocalRecipients();
    const index = all.findIndex(r => r.id === recipientRecordId || (r.notification_id === recipientRecordId)); 
    if (index !== -1) {
        all[index].is_read = true;
        all[index].read_at = new Date().toISOString();
        saveLocalRecipients(all);
    }
};

export const deleteNotification = async (id) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    let notifications = getLocalNotifications();
    notifications = notifications.filter(n => n.id !== id);
    saveLocalNotifications(notifications);
    
    let recipients = getLocalRecipients();
    recipients = recipients.filter(r => r.notification_id !== id);
    saveLocalRecipients(recipients);
    return true;
};