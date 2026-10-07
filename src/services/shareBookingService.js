import { supabase } from '@/lib/customSupabaseClient';

/**
 * Creates a new share booking record.
 * @param {object} bookingData - Contains member_id, shares_booked, total_amount.
 */
export const createShareBooking = async (bookingData) => {
    try {
        if (!bookingData.member_id || !bookingData.shares_booked) {
            throw new Error("Member ID and number of shares are required.");
        }

        const payload = {
            ...bookingData,
            payment_status: 'pending', // Always pending on creation
            booking_date: new Date().toISOString()
        };

        const { data, error } = await supabase
            .from('share_bookings') // Assuming table name is 'share_bookings'
            .insert([payload])
            .select()
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error creating share booking:", error);
        throw new Error(error.message || "Failed to create share booking.");
    }
};

/**
 * Updates an existing share booking.
 * @param {string} bookingId - The ID of the booking to update.
 * @param {object} updates - An object with fields to update (e.g., shares_booked, payment_status).
 */
export const updateShareBooking = async (bookingId, updates) => {
    try {
        if (!bookingId) throw new Error("Booking ID is required for an update.");

        const { data, error } = await supabase
            .from('share_bookings')
            .update(updates)
            .eq('id', bookingId)
            .select()
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error updating share booking:", error);
        throw new Error(error.message || "Failed to update booking.");
    }
};

/**
 * Deletes a share booking.
 * @param {string} bookingId - The ID of the booking to delete.
 */
export const deleteShareBooking = async (bookingId) => {
    try {
        const { error } = await supabase
            .from('share_bookings')
            .delete()
            .eq('id', bookingId);
        
        if (error) throw error;
        return true;
    } catch (error) {
        console.error("Error deleting share booking:", error);
        throw new Error("Failed to delete booking.");
    }
};

/**
 * Fetches all share bookings.
 */
export const getShareBookings = async () => {
    try {
        const { data, error } = await supabase
            .from('share_bookings')
            .select(`
                *,
                shareholders ( name, email )
            `)
            .order('booking_date', { ascending: false });

        if (error) throw error;
        return data || [];
    } catch (error) {
        console.error("Error fetching share bookings:", error);
        throw new Error("Could not retrieve share bookings.");
    }
};

/**
 * Fetches share bookings for a specific member.
 * @param {string} memberId - The UUID of the member.
 */
export const getShareBookingsByMember = async (memberId) => {
    try {
        if (!memberId) throw new Error("Member ID is required.");
        
        const { data, error } = await supabase
            .from('share_bookings')
            .select('*')
            .eq('member_id', memberId)
            .order('booking_date', { ascending: false });

        if (error) throw error;
        return data || [];
    } catch (error) {
        console.error("Error fetching member's bookings:", error);
        throw new Error("Could not retrieve bookings for this member.");
    }
};