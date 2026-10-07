import { supabase } from '@/lib/customSupabaseClient';

/**
 * Service to manage course registrations.
 */

// Fetch all registrations
export const getAllRegistrations = async () => {
    try {
        const { data, error } = await supabase
            .from('registrations')
            .select('*')
            .order('created_at', { ascending: false });

        if (error) throw error;
        return data || [];
    } catch (error) {
        console.error("Error fetching registrations:", error);
        throw new Error("Could not fetch registration list.");
    }
};

// Fetch a single registration by ID
export const getRegistrationById = async (id) => {
    try {
        if (!id) throw new Error("Registration ID is required");
        
        const { data, error } = await supabase
            .from('registrations')
            .select('*')
            .eq('id', id)
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error fetching registration:", error);
        throw new Error("Could not fetch registration details.");
    }
};

// Create a new registration
export const createRegistration = async (registrationData) => {
    try {
        // Validation
        if (!registrationData.client_name || !registrationData.client_email) {
            throw new Error("Client Name and Email are required.");
        }
        
        if (!registrationData.course_ids || registrationData.course_ids.length === 0) {
             throw new Error("At least one course must be selected.");
        }

        // Prepare payload
        const payload = {
            client_name: registrationData.client_name,
            client_email: registrationData.client_email,
            client_phone: registrationData.client_phone || null,
            company_name: registrationData.company_name || null,
            course_ids: registrationData.course_ids,
            total_price: registrationData.total_price,
            status: registrationData.status || 'pending',
            payment_status: registrationData.payment_status || 'pending',
            payment_id: registrationData.payment_id || null,
            payment_date: registrationData.payment_date || null,
            created_at: new Date().toISOString()
        };

        const { data, error } = await supabase
            .from('registrations')
            .insert([payload])
            .select()
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error creating registration:", error);
        throw new Error(error.message || "Failed to submit registration.");
    }
};

export const updateRegistration = async (id, updates) => {
    try {
        if (!id) throw new Error("ID required");

        const { data, error } = await supabase
            .from('registrations')
            .update(updates)
            .eq('id', id)
            .select()
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error updating registration:", error);
        throw error;
    }
};

export const deleteRegistration = async (id) => {
    try {
        if (!id) throw new Error("ID required");
        const { error } = await supabase.from('registrations').delete().eq('id', id);
        if (error) throw error;
        return true;
    } catch (error) {
        console.error("Error deleting registration:", error);
        throw error;
    }
};