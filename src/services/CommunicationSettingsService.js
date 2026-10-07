// Mock Service for Communication Settings

const COMM_SETTINGS_KEY = 'ab_communication_settings';

const defaultSettings = {
    email_notifications: true,
    sms_notifications: false,
    default_retention_days: 90,
    auto_archive: false,
    default_letter_template: 'Standard Business',
    default_notification_template: 'Simple Alert',
    updated_at: new Date().toISOString()
};

export const getSettings = async () => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const stored = localStorage.getItem(COMM_SETTINGS_KEY);
    return stored ? JSON.parse(stored) : defaultSettings;
};

export const updateSettings = async (settings) => {
    await new Promise(resolve => setTimeout(resolve, 400));
    const updated = { ...settings, updated_at: new Date().toISOString() };
    localStorage.setItem(COMM_SETTINGS_KEY, JSON.stringify(updated));
    return updated;
};