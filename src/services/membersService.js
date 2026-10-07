import { supabase } from '@/lib/customSupabaseClient';

/**
 * Service for managing member data in Supabase.
 * Handles Create, Read, Update, and Delete operations for the 'members' table.
 */

// Fetch all members
export const getAllMembers = async () => {
  try {
    const { data, error } = await supabase
      .from('members')
      .select('*')
      .order('created_at', { ascending: true });

    if (error) throw error;
    return data || [];
  } catch (error) {
    console.error("getAllMembers Error:", error);
    throw error;
  }
};

export const createMember = async (memberData) => {
  try {
    // Explicitly map fields including photo_url
    const payload = {
      name: memberData.name,
      title: memberData.title,
      description: memberData.description || '',
      email: memberData.email || null,
      phone: memberData.phone || null,
      photo_url: memberData.photo_url || null,
      country: memberData.country || null,
    };

    const { data, error } = await supabase
      .from('members')
      .insert([payload])
      .select()
      .single();

    if (error) throw error;
    return data;
  } catch (error) {
    console.error("createMember Error:", error);
    throw error;
  }
};

export const updateMember = async (id, updates) => {
  try {
    const { data, error } = await supabase
      .from('members')
      .update(updates)
      .eq('id', id)
      .select()
      .single();

    if (error) throw error;
    return data;
  } catch (error) {
    console.error("updateMember Error:", error);
    throw error;
  }
};

export const deleteMember = async (id) => {
  try {
    console.log(`[MembersService] Deleting member ${id}...`);
    
    // 1. Delete the record
    const { error } = await supabase
      .from('members')
      .delete()
      .eq('id', id);

    if (error) {
        console.error("Delete SQL Error:", error);
        throw new Error(`Database error: ${error.message}`);
    }

    // 2. Verification (Optional but good for debugging)
    const { data: check } = await supabase
        .from('members')
        .select('id')
        .eq('id', id)
        .maybeSingle();
        
    if (check) {
        throw new Error("Member still exists after deletion attempt (RLS issue?).");
    }

    console.log(`[MembersService] Member ${id} deleted successfully.`);
    return true;
  } catch (error) {
    console.error("deleteMember Exception:", error);
    throw error;
  }
};