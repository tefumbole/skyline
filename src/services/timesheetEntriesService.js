// Mock Service for TimeSheet Entries (localStorage based)

const ENTRIES_KEY = 'ab_timesheet_entries';

const getEntries = () => {
  return JSON.parse(localStorage.getItem(ENTRIES_KEY) || '[]');
};

const saveEntries = (data) => {
  localStorage.setItem(ENTRIES_KEY, JSON.stringify(data));
};

export const fetchEntries = async (userId) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  const all = getEntries();
  if(!userId) return all;
  // Sort newest first
  return all.filter(e => e.employee_id === userId).sort((a, b) => new Date(b.date) - new Date(a.date));
};

export const createEntry = async (entryData) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  const current = getEntries();
  
  // Validation: Check total hours for the day?
  const existingForDay = current.filter(e => e.employee_id === entryData.employee_id && e.date === entryData.date);
  const totalHours = existingForDay.reduce((sum, e) => sum + parseFloat(e.hours), 0);
  
  if (totalHours + parseFloat(entryData.hours) > 24) {
      throw new Error("Total hours for this day cannot exceed 24.");
  }

  const newEntry = {
    id: `entry-${Date.now()}`,
    status: 'pending',
    created_at: new Date().toISOString(),
    ...entryData
  };
  current.push(newEntry);
  saveEntries(current);
  return newEntry;
};

export const updateEntry = async (id, updates) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  const current = getEntries();
  const index = current.findIndex(e => e.id === id);
  if (index === -1) throw new Error("Entry not found");
  
  // Basic update
  current[index] = { ...current[index], ...updates };
  saveEntries(current);
  return current[index];
};

export const deleteEntry = async (id) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  let current = getEntries();
  current = current.filter(e => e.id !== id);
  saveEntries(current);
  return true;
};