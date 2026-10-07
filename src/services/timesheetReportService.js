import { fetchEntries } from './timesheetEntriesService';
import { getWorkingWeek } from './workingWeekService';

export const generateTimesheetReport = async (filters) => {
    await new Promise(resolve => setTimeout(resolve, 500));
    
    let entries = await fetchEntries(filters.employee_id === 'all' ? null : filters.employee_id);
    
    // Apply Date Filters
    if (filters.startDate) {
        entries = entries.filter(e => new Date(e.date) >= new Date(filters.startDate));
    }
    if (filters.endDate) {
        entries = entries.filter(e => new Date(e.date) <= new Date(filters.endDate));
    }

    // Enrich with expected hours difference (daily basis approximation)
    const reportData = await Promise.all(entries.map(async (entry) => {
        const settings = await getWorkingWeek(entry.employee_id);
        // Determine day of week
        const dayOfWeek = new Date(entry.date).toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
        const isWorkDay = settings[dayOfWeek];
        const expected = isWorkDay ? parseFloat(settings.expected_hours_per_day) : 0;
        
        return {
            ...entry,
            expected_hours: expected,
            difference: parseFloat(entry.hours) - expected,
            employee_name: entry.employee_name || 'Employee' // Mock or fetch
        };
    }));

    return reportData;
};