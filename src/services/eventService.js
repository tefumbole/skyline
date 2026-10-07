import { supabase } from '@/lib/customSupabaseClient';

export const createEvent = async (eventData) => {
    console.log('[EVENT] Creating event:', eventData.name);
    try {
        const { data, error } = await supabase.from('events').insert([{
            name: eventData.name,
            event_name: eventData.name,
            title: eventData.name,
            description: eventData.description,
            event_date: eventData.date,
            event_time: eventData.time,
            location: eventData.location || 'TBA',
            banner_url: eventData.banner_url,
            specify_meals: eventData.specify_meals ? 1 : 0,
            meals_json: eventData.meals_json || [],
        }]).select().single();
        
        if (error) {
          console.error('[EVENT] Create error:', error);
          throw error;
        }
        
        console.log('[EVENT] Event created successfully, ID:', data.id);
        return { 
            success: true, 
            data: { ...data, date: data.event_date, time: data.event_time } 
        };
    } catch (error) {
        console.error("[EVENT] Error creating event:", error);
        return { success: false, error: error.message };
    }
};

export const getAllEvents = async (filters = {}) => {
    console.log('[EVENT] Fetching all events');
    console.log('[EVENT] Filters:', filters);
    
    try {
        let query = supabase
            .from('events')
            .select('*', { count: 'exact' });

        // Apply filters if provided
        if (filters.status) {
            query = query.eq('status', filters.status);
            console.log('[EVENT] Filtering by status:', filters.status);
        }

        // Apply pagination
        const limit = filters.limit || 100;
        const offset = filters.offset || 0;
        
        query = query
            .order('created_at', { ascending: false })
            .range(offset, offset + limit - 1);

        console.log('[EVENT] Applying pagination: limit', limit, 'offset', offset);

        const { data, error, count } = await query;
        
        if (error) {
          console.error('[EVENT] Fetch error:', error);
          throw error;
        }
        
        // Map db columns to frontend expectations
        const mappedData = data.map(e => ({
            ...e,
            date: e.event_date,
            time: e.event_time
        }));
        
        console.log('[EVENT] Fetched', mappedData.length, 'events, total:', count);
        return { success: true, data: mappedData, count };
    } catch (error) {
        console.error("[EVENT] Error fetching events:", error);
        return { success: false, error: error.message, data: [], count: 0 };
    }
};

export const getEventById = async (id) => {
    console.log('[EVENT] Fetching event by ID:', id);
    try {
        const { data, error } = await supabase.from('events').select('*').eq('id', id).single();
        if (error) {
          console.error('[EVENT] Fetch by ID error:', error);
          throw error;
        }
        
        console.log('[EVENT] Event fetched successfully');
        return { 
            success: true, 
            data: { ...data, date: data.event_date, time: data.event_time } 
        };
    } catch (error) {
        console.error('[EVENT] Error fetching event:', error);
        return { success: false, error: error.message };
    }
};

export const updateEvent = async (id, updates) => {
    console.log('[EVENT] Updating event:', id);
    console.log('[EVENT] Updates:', updates);
    
    try {
        const { data, error } = await supabase.from('events').update(updates).eq('id', id).select().single();
        if (error) {
          console.error('[EVENT] Update error:', error);
          throw error;
        }
        
        console.log('[EVENT] Event updated successfully');
        return { success: true, data };
    } catch (error) {
        console.error('[EVENT] Error updating event:', error);
        return { success: false, error: error.message };
    }
};

export const deleteEvent = async (id) => {
    console.log('[EVENT] Deleting event:', id);
    try {
        const { error } = await supabase.from('events').delete().eq('id', id);
        if (error) {
          console.error('[EVENT] Delete error:', error);
          throw error;
        }
        
        console.log('[EVENT] Event deleted successfully');
        return { success: true };
    } catch (error) {
        console.error('[EVENT] Error deleting event:', error);
        return { success: false, error: error.message };
    }
};

export const getEventRegistrations = async (eventId) => {
    console.log('[EVENT] Fetching registrations for event:', eventId);
    try {
        const { data, error } = await supabase.from('event_registrations').select('*').eq('event_id', eventId);
        if (error) {
          console.error('[EVENT] Fetch registrations error:', error);
          throw error;
        }
        
        console.log('[EVENT] Fetched', data?.length, 'registrations');
        return { data, error: null };
    } catch (error) {
        console.error("[EVENT] Error fetching registrations:", error);
        return { data: null, error };
    }
};

export const getRegistrationById = async (registrationId) => {
    console.log('[EVENT] Fetching registration:', registrationId);
    return { data: null, error: new Error("Not implemented yet") };
};

export const approveEventRegistration = async (registrationId) => {
    console.log('[EVENT] Approving registration:', registrationId);
    return { data: null, error: new Error("Not implemented yet") };
};

export const rejectEventRegistration = async (registrationId, reason = '') => {
    console.log('[EVENT] Rejecting registration:', registrationId, 'Reason:', reason);
    return { data: null, error: new Error("Not implemented yet") };
};

export const getEventStats = async (eventId) => {
    console.log('[EVENT] Fetching stats for event:', eventId);
    try {
        const { data: invs, error } = await supabase.from('invitations').select('invitation_type, checked_in, status').eq('event_id', eventId);
        if (error) {
          console.error('[EVENT] Fetch stats error:', error);
          throw error;
        }
        
        const stats = {
            totalInvitations: invs.length,
            vipCount: invs.filter(i => i.invitation_type === 'VIP').length,
            governmentCount: invs.filter(i => i.invitation_type === 'Government').length,
            boardMemberCount: 0,
            staffCount: 0,
            standardCount: invs.filter(i => i.invitation_type === 'Standard').length,
            checkedInCount: invs.filter(i => i.checked_in).length,
            sentCount: invs.filter(i => i.status === 'Sent').length,
            failedCount: invs.filter(i => i.status === 'Failed').length,
            pendingCount: invs.filter(i => i.status === 'Pending').length
        };
        
        console.log('[EVENT] Stats calculated:', stats);
        return { data: stats, error: null };
    } catch (error) {
        console.error("[EVENT] Error fetching event stats:", error);
        return { data: null, error };
    }
};