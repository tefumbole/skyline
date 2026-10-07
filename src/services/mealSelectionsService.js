import { supabase } from '@/lib/customSupabaseClient';

export const fetchEventMealSelections = async (eventId) => {
  try {
    const { data, error } = await supabase
      .from('event_meal_selections')
      .select('*')
      .eq('event_id', eventId)
      .order('created_at', { ascending: false });

    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error fetching meal selections:', error);
    return { success: false, error: error.message };
  }
};

export const submitMealSelection = async (selectionData) => {
  try {
    const { data, error } = await supabase
      .from('event_meal_selections')
      .insert([selectionData])
      .select()
      .single();

    if (error) {
      if (error.code === '23505') { // Unique violation
        throw new Error('A meal selection has already been submitted for this phone number.');
      }
      throw error;
    }
    return { success: true, data };
  } catch (error) {
    console.error('Error submitting meal selection:', error);
    return { success: false, error: error.message };
  }
};

export const calculateMealSummary = (selections = []) => {
  const summary = {
    total: selections.length,
    starters: {},
    mainCourses: {},
    sideDishes: {},
    desserts: {}
  };

  selections.forEach(sel => {
    // Count Starters
    if (sel.starter) summary.starters[sel.starter] = (summary.starters[sel.starter] || 0) + 1;
    // Count Main Courses
    if (sel.main_course) summary.mainCourses[sel.main_course] = (summary.mainCourses[sel.main_course] || 0) + 1;
    // Count Side Dishes
    if (sel.side_dish) summary.sideDishes[sel.side_dish] = (summary.sideDishes[sel.side_dish] || 0) + 1;
    // Count Desserts
    if (sel.dessert) summary.desserts[sel.dessert] = (summary.desserts[sel.dessert] || 0) + 1;
  });

  return summary;
};

export const exportToCSV = (selections, eventName) => {
  if (!selections || selections.length === 0) return;

  const headers = ['Name', 'Phone', 'Starter', 'Main Course', 'Side Dish', 'Dessert', 'Date Submitted'];
  const rows = selections.map(s => [
    `"${s.name.replace(/"/g, '""')}"`,
    `"${s.phone}"`,
    `"${s.starter}"`,
    `"${s.main_course}"`,
    `"${s.side_dish}"`,
    `"${s.dessert}"`,
    `"${new Date(s.created_at).toLocaleString()}"`
  ]);

  const csvContent = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
  
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', `Meal_Selections_${eventName.replace(/[^a-z0-9]/gi, '_')}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};