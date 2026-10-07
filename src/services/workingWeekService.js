// Service for Working Week Configuration

const WORKING_WEEK_KEY = 'ab_working_week';

const getAllSettings = () => JSON.parse(localStorage.getItem(WORKING_WEEK_KEY) || '{}'); // Map of userId -> settings

export const getWorkingWeek = async (userId) => {
    await new Promise(resolve => setTimeout(resolve, 200));
    const all = getAllSettings();
    return all[userId] || {
        employee_id: userId,
        monday: true,
        tuesday: true,
        wednesday: true,
        thursday: true,
        friday: true,
        saturday: false,
        sunday: false,
        // New Time based fields with defaults
        monday_start: '08:00', monday_end: '17:00',
        tuesday_start: '08:00', tuesday_end: '17:00',
        wednesday_start: '08:00', wednesday_end: '17:00',
        thursday_start: '08:00', thursday_end: '17:00',
        friday_start: '08:00', friday_end: '17:00',
        saturday_start: '00:00', saturday_end: '00:00',
        sunday_start: '00:00', sunday_end: '00:00',
        lunch_break_minutes: 60,
        expected_hours_per_day: 8.0, // Legacy/Fallback
        created_at: new Date().toISOString()
    };
};

export const updateWorkingWeek = async (userId, settings) => {
    await new Promise(resolve => setTimeout(resolve, 300));
    const all = getAllSettings();
    const updated = {
        ...all[userId],
        ...settings,
        employee_id: userId,
        updated_at: new Date().toISOString()
    };
    all[userId] = updated;
    localStorage.setItem(WORKING_WEEK_KEY, JSON.stringify(all));
    return updated;
};

export const calculateDailyHours = (startTime, endTime, lunchMinutes) => {
    if (!startTime || !endTime) return 0;
    
    const [startH, startM] = startTime.split(':').map(Number);
    const [endH, endM] = endTime.split(':').map(Number);
    
    const start = new Date(0, 0, 0, startH, startM, 0);
    const end = new Date(0, 0, 0, endH, endM, 0);
    
    let diffMs = end - start;
    if (diffMs < 0) diffMs += 24 * 60 * 60 * 1000; // Handle overnight if needed, though rare for standard shift
    
    let diffMinutes = diffMs / 1000 / 60;
    diffMinutes -= (lunchMinutes || 0);
    
    return Math.max(0, diffMinutes / 60);
};

export const calculateWeeklyExpectedHours = (settings) => {
    if (!settings) return 0;
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    let total = 0;
    
    days.forEach(day => {
        if (settings[day]) {
            const start = settings[`${day}_start`];
            const end = settings[`${day}_end`];
            // Use specific day hours if available, otherwise fallback to generic calculation if implemented that way
            // For now assuming each day has its own start/end in settings
            if (start && end) {
                total += calculateDailyHours(start, end, settings.lunch_break_minutes || 0);
            } else {
                total += parseFloat(settings.expected_hours_per_day || 0);
            }
        }
    });
    
    return total;
};