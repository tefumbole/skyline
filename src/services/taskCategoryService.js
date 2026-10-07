import { supabase } from '@/lib/customSupabaseClient';

export const getTaskCategories = async () => {
  try {
    const { data, error } = await supabase
      .from('task_categories')
      .select('*')
      .order('name');
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error fetching categories:', error);
    return { success: false, error: error.message };
  }
};

export const createTaskCategory = async (categoryData) => {
  try {
    const { data, error } = await supabase
      .from('task_categories')
      .insert([categoryData])
      .select()
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error creating category:', error);
    return { success: false, error: error.message };
  }
};

export const updateTaskCategory = async (id, categoryData) => {
  try {
    const { data, error } = await supabase
      .from('task_categories')
      .update(categoryData)
      .eq('id', id)
      .select()
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error updating category:', error);
    return { success: false, error: error.message };
  }
};

export const deleteTaskCategory = async (id) => {
  try {
    const { error } = await supabase
      .from('task_categories')
      .delete()
      .eq('id', id);
    if (error) throw error;
    return { success: true };
  } catch (error) {
    console.error('Error deleting category:', error);
    return { success: false, error: error.message };
  }
};