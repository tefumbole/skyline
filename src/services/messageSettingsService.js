import { supabase } from '@/lib/customSupabaseClient';

const BUCKET_NAME = 'system-assets';

export const getMessageSettings = async () => {
  try {
    const { data, error } = await supabase
      .from('message_settings')
      .select('*')
      .eq('singleton_key', true)
      .single();

    if (error && error.code !== 'PGRST116') {
      console.error('Error fetching message settings:', error);
      throw error;
    }
    return data || null;
  } catch (error) {
    console.error('Exception fetching message settings:', error);
    throw error;
  }
};

export const updateMessageSettings = async (updates) => {
  try {
    const { data, error } = await supabase
      .from('message_settings')
      .update(updates)
      .eq('singleton_key', true)
      .select()
      .single();

    if (error) throw error;
    return data;
  } catch (error) {
    console.error('Exception updating message settings:', error);
    throw error;
  }
};

export const uploadMessageAttachment = async (file, type = 'header') => {
  if (!file) throw new Error('No file provided');

  // Validate file type
  const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
  if (!allowedTypes.includes(file.type)) {
    throw new Error('Invalid file type. Only PNG, JPG, JPEG, and WebP are allowed.');
  }

  // Validate size (5MB max)
  const maxSize = 5 * 1024 * 1024;
  if (file.size > maxSize) {
    throw new Error('File too large. Maximum size is 5MB.');
  }

  const fileExt = file.name.split('.').pop();
  const fileName = `msg-${type}-${Date.now()}.${fileExt}`;
  const filePath = `messaging/${fileName}`;

  try {
    const { error: uploadError } = await supabase.storage
      .from(BUCKET_NAME)
      .upload(filePath, file, { upsert: true });

    if (uploadError) throw uploadError;

    const { data: { publicUrl } } = supabase.storage
      .from(BUCKET_NAME)
      .getPublicUrl(filePath);

    return publicUrl;
  } catch (error) {
    console.error(`Error uploading ${type} attachment:`, error);
    throw error;
  }
};

export const deleteMessageAttachment = async (fileUrl) => {
  if (!fileUrl) return true;
  
  try {
    // Extract file path from URL
    const urlParts = fileUrl.split('/');
    const bucketIndex = urlParts.findIndex(part => part === BUCKET_NAME);
    if (bucketIndex === -1) return true; // Not our bucket or malformed URL
    
    const filePath = urlParts.slice(bucketIndex + 1).join('/');
    
    const { error } = await supabase.storage
      .from(BUCKET_NAME)
      .remove([filePath]);

    if (error) throw error;
    return true;
  } catch (error) {
    console.error('Error deleting attachment:', error);
    throw error;
  }
};