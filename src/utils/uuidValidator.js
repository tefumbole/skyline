import { supabase } from '@/lib/customSupabaseClient';

/**
 * Validates if a given string is a correctly formatted UUID (v4).
 * @param {string} uuid - The string to validate
 * @returns {boolean} - True if valid UUID, false otherwise
 */
export const isValidUUID = (uuid) => {
  if (!uuid || typeof uuid !== 'string') return false;
  const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
  return uuidRegex.test(uuid.trim());
};

/**
 * Checks if an event exists in the database by its UUID.
 * @param {string} eventId - The UUID of the event
 * @returns {Promise<boolean>} - True if event exists, false otherwise
 */
export const checkEventExists = async (eventId) => {
  if (!isValidUUID(eventId)) return false;
  
  try {
    const { data, error } = await supabase
      .from('events')
      .select('id')
      .eq('id', eventId.trim())
      .single();
      
    if (error || !data) {
      console.warn(`Event validation failed for ID ${eventId}:`, error?.message || 'Not found');
      return false;
    }
    
    return true;
  } catch (error) {
    console.error(`Error checking event existence:`, error);
    return false;
  }
};

/**
 * Checks if a template exists in the database by its UUID.
 * @param {string} templateId - The UUID of the template
 * @returns {Promise<boolean>} - True if template exists, false otherwise
 */
export const checkTemplateExists = async (templateId) => {
  if (!isValidUUID(templateId)) return false;
  
  try {
    const { data, error } = await supabase
      .from('invitation_templates')
      .select('id')
      .eq('id', templateId.trim())
      .single();
      
    if (error || !data) return false;
    return true;
  } catch (error) {
    console.error(`Error checking template existence:`, error);
    return false;
  }
};