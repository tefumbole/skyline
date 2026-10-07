import { supabase } from '@/lib/customSupabaseClient';
import QRCode from 'qrcode';
import html2canvas from 'html2canvas';

export const generateInvitationId = () => {
    const year = new Date().getFullYear();
    const randomNum = Math.floor(10000 + Math.random() * 90000);
    return `EVENT-${year}-${randomNum}`;
};

export const generateQRCode = async (dataString) => {
    try {
        return await QRCode.toDataURL(dataString, { width: 300, margin: 2 });
    } catch (err) {
        console.error("QR Generation failed", err);
        return null;
    }
};

export const generateInvitationCard = async (elementId) => {
    try {
        const element = document.getElementById(elementId);
        if (!element) throw new Error("Element not found");
        const canvas = await html2canvas(element, { scale: 2, useCORS: true });
        return canvas.toDataURL('image/png');
    } catch (err) {
        console.error("Card generation failed", err);
        return null;
    }
};

export const createInvitation = async (invitationData) => {
    try {
        // 1. Create Guest Record
        const { data: guest, error: guestErr } = await supabase.from('guests').insert([{
            name: invitationData.guest_name,
            phone: invitationData.phone,
            email: invitationData.email
        }]).select().single();
        
        if (guestErr) throw guestErr;

        const invCode = generateInvitationId();

        // 2. Create Invitation Record linking Event and Guest
        const { data: inv, error: invErr } = await supabase.from('invitations').insert([{
            event_id: invitationData.event_id,
            guest_id: guest.id,
            invitation_type: invitationData.category,
            qr_code: invCode,
            status: 'Pending'
        }]).select().single();
        
        if (invErr) throw invErr;

        return { 
            success: true, 
            data: { 
                ...inv, 
                id: inv.id,
                invitation_code: invCode, 
                guest_name: guest.name, 
                phone: guest.phone, 
                category: inv.invitation_type 
            } 
        };
    } catch (error) {
        console.error("Error creating invitation:", error);
        return { success: false, error: error.message };
    }
};

export const getAllInvitations = async () => {
    try {
        const { data, error } = await supabase.from('invitations').select(`
            *,
            guests (name, phone, email)
        `).order('created_at', { ascending: false });
        
        if (error) throw error;
        
        const mappedData = data.map(i => ({
            id: i.id,
            event_id: i.event_id,
            invitation_code: i.qr_code,
            guest_name: i.guests?.name || 'Unknown',
            phone: i.guests?.phone,
            email: i.guests?.email,
            category: i.invitation_type,
            status: i.status,
            delivery_status: i.status,
            checked_in: i.checked_in
        }));
        
        return { success: true, data: mappedData };
    } catch (error) {
        console.error("Error fetching invitations:", error);
        return { success: false, error: error.message };
    }
};

export const getInvitationsByEvent = async (eventId) => {
    try {
        const { data, error } = await supabase.from('invitations').select(`*, guests (name, phone, email)`).eq('event_id', eventId);
        if (error) throw error;
        return { success: true, data };
    } catch (error) {
        return { success: false, error: error.message };
    }
};

export const getInvitationById = async (id) => {
    try {
        const { data, error } = await supabase.from('invitations').select(`*, guests (name, phone, email)`).eq('id', id).single();
        if (error) throw error;
        return { success: true, data };
    } catch (error) {
        return { success: false, error: error.message };
    }
};

export const getInvitationByInvitationId = async (invitationId) => {
    try {
        const { data, error } = await supabase.from('invitations').select(`*, guests (name, phone, email)`).eq('qr_code', invitationId).single();
        if (error) throw error;
        return { success: true, data };
    } catch (error) {
        return { success: false, error: error.message };
    }
};

export const updateInvitationStatus = async (id, status) => {
    try {
        const { data, error } = await supabase.from('invitations')
            .update({ status: status })
            .eq('id', id)
            .select()
            .single();
        if (error) throw error;
        return { success: true, data };
    } catch (error) {
        return { success: false, error: error.message };
    }
};

export const checkInInvitation = async (code) => {
    try {
        // Find invitation safely (prevent uuid cast errors by avoiding `or` with non-uuids)
        const isUUID = code.match(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
        let query = supabase.from('invitations').select('*, guests(name)');
        
        if (isUUID) {
            query = query.or(`qr_code.eq.${code},id.eq.${code}`);
        } else {
            query = query.eq('qr_code', code);
        }
        
        const { data: inv, error: findErr } = await query.single();
        
        if (findErr || !inv) throw new Error("Invalid QR Code / Invitation not found");
        if (inv.checked_in) throw new Error("Guest is already checked in!");

        const { data: updated, error: updateErr } = await supabase.from('invitations')
            .update({ checked_in: true, checked_in_at: new Date().toISOString() })
            .eq('id', inv.id)
            .select('*, guests(name)')
            .single();
        
        if (updateErr) throw updateErr;

        return { 
            success: true, 
            data: { 
                guest_name: updated.guests?.name, 
                category: updated.invitation_type 
            } 
        };
    } catch (error) {
        return { success: false, error: error.message };
    }
};

export const deleteInvitation = async (id) => {
    try {
        const { error } = await supabase.from('invitations').delete().eq('id', id);
        if (error) throw error;
        return { success: true };
    } catch (error) {
        return { success: false, error: error.message };
    }
};

export const resendInvitation = async (id) => {
    try {
        return updateInvitationStatus(id, 'Sent');
    } catch (error) {
        return { success: false, error: error.message };
    }
};