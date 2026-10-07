// Mock Service for Activities (localStorage based)

const ACTIVITIES_KEY = 'ab_timesheet_activities';

const getActivities = () => {
  return JSON.parse(localStorage.getItem(ACTIVITIES_KEY) || '[]');
};

const saveActivities = (data) => {
  localStorage.setItem(ACTIVITIES_KEY, JSON.stringify(data));
};

export const fetchActivities = async (userId) => {
  // Simulate delay
  await new Promise(resolve => setTimeout(resolve, 300));
  const all = getActivities();
  if (!userId) return all; // Admin sees all? Or filtering needed
  return all.filter(a => a.employee_id === userId);
};

export const createActivity = async (activityData) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  const current = getActivities();
  const newActivity = {
    id: `act-${Date.now()}`,
    ...activityData,
    created_at: new Date().toISOString()
  };
  current.push(newActivity);
  saveActivities(current);
  return newActivity;
};

export const updateActivity = async (id, updates) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  const current = getActivities();
  const index = current.findIndex(a => a.id === id);
  if (index === -1) throw new Error("Activity not found");

  current[index] = { ...current[index], ...updates };
  saveActivities(current);
  return current[index];
};

export const deleteActivity = async (id) => {
  await new Promise(resolve => setTimeout(resolve, 300));
  let current = getActivities();
  current = current.filter(a => a.id !== id);
  saveActivities(current);
  return true;
};