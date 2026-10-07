import { supabase } from '@/lib/customSupabaseClient';

export const getMessageTemplates = async () => {
  try {
    const { data, error } = await supabase
      .from('task_message_templates')
      .select('*')
      .order('type');
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error fetching templates:', error);
    return { success: false, error: error.message };
  }
};

export const getTemplateByType = async (type) => {
  try {
    const { data, error } = await supabase
      .from('task_message_templates')
      .select('*')
      .eq('type', type)
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error(`Error fetching template ${type}:`, error);
    return { success: false, error: error.message };
  }
};

export const updateMessageTemplate = async (id, templateData) => {
  try {
    const { data, error } = await supabase
      .from('task_message_templates')
      .update(templateData)
      .eq('id', id)
      .select()
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error updating template:', error);
    return { success: false, error: error.message };
  }
};

export const replaceTemplateVariables = (text, variables) => {
  if (!text) return '';
  let result = text;
  Object.keys(variables).forEach(key => {
    const value = variables[key] || '';
    const regex = new RegExp(`{{${key}}}`, 'g');
    result = result.replace(regex, value);
  });
  return result;
};