// Mock Overtime Calculation Service

import { fetchEntries } from './timesheetEntriesService';
import { getWorkingWeek, calculateWeeklyExpectedHours } from './workingWeekService';

// This would typically be a backend job or DB view, here we simulate calculation
export const getOvertimeReport = async (filters) => {
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // 1. Get all relevant entries
    const allEntries = await fetchEntries(filters.employee_id === 'all' ? null : filters.employee_id);
    
    // 2. Group entries by Employee and Week
    // Helper to get week key (YYYY-Www)
    const getWeekKey = (date) => {
        const d = new Date(date);
        d.setHours(0,0,0,0);
        d.setDate(d.getDate() + 4 - (d.getDay()||7));
        const yearStart = new Date(d.getFullYear(),0,1);
        const weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1)/7);
        return `${d.getFullYear()}-W${weekNo}`;
    };

    const grouped = {};
    
    // Since entries store employee ID, we might need to fetch user details (mocked here)
    // Assuming filters.employees is available or we just use ID
    
    for (const entry of allEntries) {
        if (filters.startDate && new Date(entry.date) < new Date(filters.startDate)) continue;
        if (filters.endDate && new Date(entry.date) > new Date(filters.endDate)) continue;

        const week = getWeekKey(entry.date);
        const key = `${entry.employee_id}::${week}`;
        
        if (!grouped[key]) {
            // Fetch expected hours for this employee
            const settings = await getWorkingWeek(entry.employee_id);
            const expected = calculateWeeklyExpectedHours(settings);
            
            grouped[key] = {
                id: key,
                employee_id: entry.employee_id,
                employee_name: entry.employee_name || 'Unknown', // In real app, join with users
                week: week,
                total_hours: 0,
                expected_hours: expected,
                overtime_hours: 0
            };
        }
        
        grouped[key].total_hours += parseFloat(entry.hours);
    }
    
    // Calculate overtime
    const result = Object.values(grouped).map(item => {
        const overtime = Math.max(0, item.total_hours - item.expected_hours);
        return {
            ...item,
            overtime_hours: overtime,
            overtime_pay_rate: 1.5 // Mock rate
        };
    });
    
    return result;
};