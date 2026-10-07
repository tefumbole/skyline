import { supabase } from '@/lib/customSupabaseClient';

export async function getAllMeals() {
  const { data, error } = await supabase
    .from('event_meals')
    .select('*')
    .eq('is_active', true)
    .order('sort_order', { ascending: true })
    .order('name', { ascending: true });
  if (error) throw error;
  return data || [];
}

export async function createMeal(payload) {
  const { data, error } = await supabase
    .from('event_meals')
    .insert([{
      name: payload.name,
      description: payload.description || null,
      category: payload.category || 'General',
      image_url: payload.image_url || null,
      sort_order: payload.sort_order ?? 0,
      is_active: true,
    }])
    .select()
    .single();
  if (error) throw error;
  return data;
}

export async function updateMeal(id, updates) {
  const { data, error } = await supabase
    .from('event_meals')
    .update({ ...updates, updated_at: new Date().toISOString() })
    .eq('id', id)
    .select()
    .single();
  if (error) throw error;
  return data;
}

export async function deleteMeal(id) {
  const { error } = await supabase
    .from('event_meals')
    .update({ is_active: false, updated_at: new Date().toISOString() })
    .eq('id', id);
  if (error) throw error;
  return true;
}
