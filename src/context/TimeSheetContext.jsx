import React, { createContext, useContext, useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { useToast } from '@/components/ui/use-toast';
import { fetchActivities, createActivity, updateActivity as updateActivityService, deleteActivity as deleteActivityService } from '@/services/activitiesService';
import { fetchEntries, createEntry, updateEntry as updateEntryService, deleteEntry as deleteEntryService } from '@/services/timesheetEntriesService';
import { getWorkingWeek, updateWorkingWeek } from '@/services/workingWeekService';

const TimeSheetContext = createContext({});

export const useTimeSheet = () => useContext(TimeSheetContext);

export const TimeSheetProvider = ({ children }) => {
  const { user } = useAuth();
  const { toast } = useToast();
  
  const [activities, setActivities] = useState([]);
  const [entries, setEntries] = useState([]);
  const [workingWeek, setWorkingWeek] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (user && user.id) {
        loadData();
    } else {
        setActivities([]);
        setEntries([]);
        setWorkingWeek(null);
    }
  }, [user]);

  const loadData = async () => {
      // Avoid loading if user is not valid
      if (!user?.id) return;

      setLoading(true);
      try {
          const [acts, ents, week] = await Promise.all([
              fetchActivities(user.id).catch(() => []),
              fetchEntries(user.id).catch(() => []),
              getWorkingWeek(user.id).catch(() => null)
          ]);
          setActivities(acts || []);
          setEntries(ents || []);
          setWorkingWeek(week);
      } catch (error) {
          console.error('TimeSheetContext Load Error', error);
      } finally {
          setLoading(false);
      }
  };

  const addActivity = async (data) => {
    try {
        const newAct = await createActivity({ ...data, employee_id: user.id });
        setActivities(prev => [...prev, newAct]);
        toast({ title: "Activity Created", description: "New activity added to your list." });
        return newAct;
    } catch (e) {
        toast({ title: "Error", description: e.message || "Failed to add activity", variant: "destructive" });
    }
  };

  const updateActivity = async (id, data) => {
    try {
        const updated = await updateActivityService(id, data);
        setActivities(prev => prev.map(a => a.id === id ? updated : a));
        toast({ title: "Activity Updated", description: "Changes saved successfully." });
    } catch (e) {
        toast({ title: "Error", description: e.message || "Failed to update activity", variant: "destructive" });
    }
  };

  const deleteActivity = async (id) => {
    try {
        await deleteActivityService(id);
        setActivities(prev => prev.filter(a => a.id !== id));
        toast({ title: "Activity Deleted" });
    } catch (e) {
        toast({ title: "Error", description: e.message || "Failed to delete activity", variant: "destructive" });
    }
  };

  const addEntry = async (data) => {
    try {
        const newEntry = await createEntry({ 
            ...data, 
            employee_id: user.id,
            employee_name: user.full_name 
        });
        setEntries(prev => [newEntry, ...prev]); 
        toast({ title: "Time Logged", description: "Entry saved successfully." });
        return newEntry;
    } catch (e) {
        toast({ title: "Error", description: e.message || "Failed to add entry", variant: "destructive" });
    }
  };

  const updateEntry = async (id, data) => {
    try {
        const updated = await updateEntryService(id, data);
        setEntries(prev => prev.map(e => e.id === id ? updated : e));
        toast({ title: "Entry Updated" });
    } catch (e) {
        toast({ title: "Error", description: e.message || "Failed to update entry", variant: "destructive" });
    }
  };

  const deleteEntry = async (id) => {
    try {
        await deleteEntryService(id);
        setEntries(prev => prev.filter(e => e.id !== id));
        toast({ title: "Entry Deleted" });
    } catch (e) {
         toast({ title: "Error", description: e.message || "Failed to delete entry", variant: "destructive" });
    }
  };

  const saveWorkingWeek = async (settings) => {
      try {
          const updated = await updateWorkingWeek(user.id, settings);
          setWorkingWeek(updated);
          toast({ title: "Settings Saved", description: "Work schedule updated." });
      } catch (e) {
          toast({ title: "Error", description: e.message || "Failed to save settings", variant: "destructive" });
      }
  };

  return (
    <TimeSheetContext.Provider value={{
      activities,
      entries,
      workingWeek,
      loading,
      addActivity,
      updateActivity,
      deleteActivity,
      addEntry,
      updateEntry,
      deleteEntry,
      saveWorkingWeek,
      refreshData: loadData
    }}>
      {children}
    </TimeSheetContext.Provider>
  );
};