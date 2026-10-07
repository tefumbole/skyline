import { supabase } from '@/lib/customSupabaseClient';

/**
 * Events Service
 * Handles all event-related operations including CRUD and image management
 */

/**
 * Get all published events ordered by date descending
 * @returns {Promise<Array>} Array of published events
 */
export const getPublishedEvents = async () => {
  try {
    const { data, error } = await supabase
      .from('events')
      .select('*')
      .eq('status', 'published')
      .order('event_date', { ascending: false });

    if (error) {
      console.error('eventsService: Error fetching published events:', error);
      throw error;
    }

    return data || [];
  } catch (err) {
    console.error('eventsService: Unexpected error in getPublishedEvents:', err);
    throw err;
  }
};

/**
 * Get all events (admin only)
 * @returns {Promise<Array>} Array of all events
 */
export const getAllEvents = async () => {
  try {
    const { data, error } = await supabase
      .from('events')
      .select('*')
      .order('event_date', { ascending: false });

    if (error) {
      console.error('eventsService: Error fetching all events:', error);
      throw error;
    }

    return data || [];
  } catch (err) {
    console.error('eventsService: Unexpected error in getAllEvents:', err);
    throw err;
  }
};

/**
 * Get single event by ID with related images
 * @param {string} eventId - Event UUID
 * @returns {Promise<Object>} Event object with images array
 */
export const getEventById = async (eventId) => {
  try {
    if (!eventId) {
      throw new Error('Event ID is required');
    }

    // Fetch event
    const { data: eventData, error: eventError } = await supabase
      .from('events')
      .select('*')
      .eq('id', eventId)
      .single();

    if (eventError) {
      console.error('eventsService: Error fetching event:', eventError);
      throw eventError;
    }

    if (!eventData) {
      throw new Error('Event not found');
    }

    // Fetch event images
    const { data: imagesData, error: imagesError } = await supabase
      .from('event_images')
      .select('*')
      .eq('event_id', eventId)
      .order('order', { ascending: true });

    if (imagesError) {
      console.error('eventsService: Error fetching event images:', imagesError);
      // Don't throw - event can exist without images
    }

    return {
      ...eventData,
      images: imagesData || []
    };
  } catch (err) {
    console.error('eventsService: Unexpected error in getEventById:', err);
    throw err;
  }
};

/**
 * Get recent published events for homepage
 * @param {number} limit - Number of events to fetch (default 6)
 * @returns {Promise<Array>} Array of recent published events
 */
export const getRecentEvents = async (limit = 6) => {
  try {
    const { data, error } = await supabase
      .from('events')
      .select('*')
      .eq('status', 'published')
      .order('event_date', { ascending: false })
      .limit(limit);

    if (error) {
      console.error('eventsService: Error fetching recent events:', error);
      throw error;
    }

    return data || [];
  } catch (err) {
    console.error('eventsService: Unexpected error in getRecentEvents:', err);
    throw err;
  }
};

/**
 * Create new event (admin only)
 * @param {Object} eventData - Event data object
 * @returns {Promise<Object>} Created event
 */
export const createEvent = async (eventData) => {
  try {
    if (!eventData.title || !eventData.event_date) {
      throw new Error('Title and event_date are required');
    }

    const { data: { user } } = await supabase.auth.getUser();

    const { data, error } = await supabase
      .from('events')
      .insert([{
        ...eventData,
        created_by: user?.id
      }])
      .select()
      .single();

    if (error) {
      console.error('eventsService: Error creating event:', error);
      throw error;
    }

    return data;
  } catch (err) {
    console.error('eventsService: Unexpected error in createEvent:', err);
    throw err;
  }
};

/**
 * Update existing event (admin only)
 * @param {string} eventId - Event UUID
 * @param {Object} eventData - Updated event data
 * @returns {Promise<Object>} Updated event
 */
export const updateEvent = async (eventId, eventData) => {
  try {
    if (!eventId) {
      throw new Error('Event ID is required');
    }

    const { data, error } = await supabase
      .from('events')
      .update(eventData)
      .eq('id', eventId)
      .select()
      .single();

    if (error) {
      console.error('eventsService: Error updating event:', error);
      throw error;
    }

    return data;
  } catch (err) {
    console.error('eventsService: Unexpected error in updateEvent:', err);
    throw err;
  }
};

/**
 * Delete event (admin only)
 * @param {string} eventId - Event UUID
 * @returns {Promise<boolean>} Success status
 */
export const deleteEvent = async (eventId) => {
  try {
    if (!eventId) {
      throw new Error('Event ID is required');
    }

    // Get all images for this event to delete from storage
    const { data: images } = await supabase
      .from('event_images')
      .select('image_path')
      .eq('event_id', eventId);

    // Delete images from storage
    if (images && images.length > 0) {
      const paths = images.map(img => img.image_path);
      await supabase.storage
        .from('events-images')
        .remove(paths);
    }

    // Delete event (cascade will delete event_images records)
    const { error } = await supabase
      .from('events')
      .delete()
      .eq('id', eventId);

    if (error) {
      console.error('eventsService: Error deleting event:', error);
      throw error;
    }

    return true;
  } catch (err) {
    console.error('eventsService: Unexpected error in deleteEvent:', err);
    throw err;
  }
};

/**
 * Upload event image to storage
 * @param {string} eventId - Event UUID
 * @param {File} file - Image file to upload
 * @returns {Promise<Object>} Object with publicUrl and path
 */
export const uploadEventImage = async (eventId, file) => {
  try {
    if (!eventId || !file) {
      throw new Error('Event ID and file are required');
    }

    const fileExt = file.name.split('.').pop();
    const fileName = `${Date.now()}-${Math.random().toString(36).substring(7)}.${fileExt}`;
    const filePath = `events/${eventId}/${fileName}`;

    const { error: uploadError } = await supabase.storage
      .from('events-images')
      .upload(filePath, file);

    if (uploadError) {
      console.error('eventsService: Error uploading image:', uploadError);
      throw uploadError;
    }

    const { data: { publicUrl } } = supabase.storage
      .from('events-images')
      .getPublicUrl(filePath);

    return {
      publicUrl,
      path: filePath
    };
  } catch (err) {
    console.error('eventsService: Unexpected error in uploadEventImage:', err);
    throw err;
  }
};

/**
 * Add event image record to database
 * @param {string} eventId - Event UUID
 * @param {Object} imageData - Image data (url, path, alt_text, order)
 * @returns {Promise<Object>} Created image record
 */
export const addEventImage = async (eventId, imageData) => {
  try {
    if (!eventId || !imageData.image_url || !imageData.image_path) {
      throw new Error('Event ID, image URL and path are required');
    }

    const { data, error } = await supabase
      .from('event_images')
      .insert([{
        event_id: eventId,
        ...imageData
      }])
      .select()
      .single();

    if (error) {
      console.error('eventsService: Error adding event image:', error);
      throw error;
    }

    return data;
  } catch (err) {
    console.error('eventsService: Unexpected error in addEventImage:', err);
    throw err;
  }
};

/**
 * Delete event image from storage and database
 * @param {string} imageId - Image UUID
 * @param {string} imagePath - Storage path
 * @returns {Promise<boolean>} Success status
 */
export const deleteEventImage = async (imageId, imagePath) => {
  try {
    if (!imageId || !imagePath) {
      throw new Error('Image ID and path are required');
    }

    // Delete from storage
    const { error: storageError } = await supabase.storage
      .from('events-images')
      .remove([imagePath]);

    if (storageError) {
      console.error('eventsService: Error deleting from storage:', storageError);
      // Continue even if storage delete fails
    }

    // Delete from database
    const { error: dbError } = await supabase
      .from('event_images')
      .delete()
      .eq('id', imageId);

    if (dbError) {
      console.error('eventsService: Error deleting image from database:', dbError);
      throw dbError;
    }

    return true;
  } catch (err) {
    console.error('eventsService: Unexpected error in deleteEventImage:', err);
    throw err;
  }
};

export default {
  getPublishedEvents,
  getAllEvents,
  getEventById,
  getRecentEvents,
  createEvent,
  updateEvent,
  deleteEvent,
  uploadEventImage,
  addEventImage,
  deleteEventImage
};