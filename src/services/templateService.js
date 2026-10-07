import { supabase } from '@/lib/customSupabaseClient';

// --- INVITATION TEMPLATES ---

export const getAllTemplates = async (userId) => {
  try {
    let query = supabase.from('invitation_templates').select('*');
    if (userId) {
      query = query.eq('user_id', userId);
    }
    const { data, error } = await query.order('created_at', { ascending: false });
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error fetching invitation templates:', error);
    return { success: false, error: error.message };
  }
};

export const getTemplate = async (templateId) => {
  try {
    const { data, error } = await supabase
      .from('invitation_templates')
      .select('*')
      .eq('id', templateId)
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    return { success: false, error: error.message };
  }
};

export const createTemplate = async (userId, templateData) => {
  try {
    if (!templateData || !templateData.name || !templateData.name.trim()) {
      throw new Error('Template name is required');
    }

    const trimmedName = templateData.name.trim();

    const insertPayload = {
      ...templateData,
      user_id: userId,
      name: trimmedName,
      template_name: trimmedName, // Required to satisfy NOT NULL constraint
      template_layout: templateData.layout_type || 'default' // Required to satisfy NOT NULL constraint
    };

    const { data, error } = await supabase
      .from('invitation_templates')
      .insert([insertPayload])
      .select()
      .single();
      
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error creating template:', error);
    return { success: false, error: error.message };
  }
};

export const updateInvitationTemplate = async (templateId, templateData) => {
  try {
    const { data, error } = await supabase
      .from('invitation_templates')
      .update(templateData)
      .eq('id', templateId)
      .select()
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    return { success: false, error: error.message };
  }
};

export const deleteInvitationTemplate = async (templateId, imagePath) => {
  try {
    if (imagePath) {
      const { error: storageError } = await supabase.storage
        .from('invitation-templates')
        .remove([imagePath]);
      if (storageError) console.error('Storage deletion error:', storageError);
    }
    const { error } = await supabase
      .from('invitation_templates')
      .delete()
      .eq('id', templateId);
    if (error) throw error;
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
};

export const uploadTemplateImage = async (userId, file) => {
  try {
    const fileExt = file.name.split('.').pop();
    const fileName = `${userId}/${Date.now()}_${Math.random().toString(36).substring(7)}.${fileExt}`;
    
    const { error: uploadError, data } = await supabase.storage
      .from('invitation-templates')
      .upload(fileName, file, { upsert: false });
      
    if (uploadError) throw uploadError;
    
    const { data: { publicUrl } } = supabase.storage
      .from('invitation-templates')
      .getPublicUrl(fileName);
      
    return { success: true, path: fileName, url: publicUrl };
  } catch (error) {
    return { success: false, error: error.message };
  }
};

export const toggleTemplateStatus = async (templateId, currentStatus) => {
  try {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const { data, error } = await supabase
      .from('invitation_templates')
      .update({ status: newStatus })
      .eq('id', templateId)
      .select()
      .single();
    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    return { success: false, error: error.message };
  }
};

// --- MESSAGE TEMPLATES (WhatsApp / Email) ---

export const getTemplates = async (userId) => {
  try {
    // userId is optional depending on callers
    const { data, error } = await supabase
      .from('message_templates')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) throw error;
    return { success: true, data: data || [] };
  } catch (error) {
    console.error('Error fetching message templates:', error);
    return { success: false, error: error.message, data: [] };
  }
};

export const saveMessageAsTemplate = async (...args) => {
  // Support both (userId, templateData) and (templateData) signatures
  const templateData = args.length === 2 ? args[1] : args[0];
  
  try {
    const { data, error } = await supabase
      .from('message_templates')
      .insert([templateData])
      .select()
      .single();

    if (error) {
      if (error.code === '23505') { // Unique violation
        throw new Error('A template with this name already exists.');
      }
      throw error;
    }
    return { success: true, data };
  } catch (error) {
    console.error('Error saving message template:', error);
    return { success: false, error: error.message };
  }
};

export const deleteTemplate = async (templateId) => {
  try {
    const { error } = await supabase
      .from('message_templates')
      .delete()
      .eq('id', templateId);

    if (error) throw error;
    return { success: true };
  } catch (error) {
    console.error('Error deleting message template:', error);
    return { success: false, error: error.message };
  }
};

export const updateTemplate = async (templateId, templateData) => {
  try {
    const { data, error } = await supabase
      .from('message_templates')
      .update(templateData)
      .eq('id', templateId)
      .select()
      .single();

    if (error) throw error;
    return { success: true, data };
  } catch (error) {
    console.error('Error updating message template:', error);
    return { success: false, error: error.message };
  }
};