import { supabase } from '@/lib/customSupabaseClient';

/**
 * Letter Management Service
 * Handles official letters and communications via Supabase.
 */

export const createLetter = async (letterData, recipients) => {
  try {
    // 1. Create the letter record
    const { data: letter, error: letterError } = await supabase
      .from('letters')
      .insert([{
        title: letterData.title,
        content: letterData.content,
        category_id: letterData.category_id,
        sent_at: new Date().toISOString(),
        is_archived: false
      }])
      .select()
      .single();

    if (letterError) throw letterError;

    // 2. Create recipient records
    if (recipients && recipients.length > 0) {
      const recipientPayload = recipients.map(r => ({
        letter_id: letter.id,
        recipient_id: r.id,
        recipient_type: r.type,
        recipient_name: r.name,
        is_read: false
      }));

      const { error: recipientError } = await supabase
        .from('letter_recipients')
        .insert(recipientPayload);

      if (recipientError) throw recipientError;
    }

    return letter;
  } catch (error) {
    console.error("Error creating letter:", error);
    throw error;
  }
};

export const getLetterHistory = async () => {
  try {
    const { data, error } = await supabase
      .from('letters')
      .select(`
        *,
        letter_recipients (
          id, recipient_id, recipient_name, recipient_type, is_read
        )
      `)
      .order('sent_at', { ascending: false });

    if (error) throw error;
    
    // Format for the UI
    return (data || []).map(l => ({
      ...l,
      recipient_count: l.letter_recipients?.length || 0,
      read_count: l.letter_recipients?.filter(r => r.is_read).length || 0,
      recipient_name: l.letter_recipients?.[0]?.recipient_name || 'Multiple Recipients',
      status: 'Sent'
    }));
  } catch (error) {
    console.error("Error fetching letter history:", error);
    return [];
  }
};

export const getLettersByRecipient = async (recipientId) => {
  try {
    const { data, error } = await supabase
      .from('letter_recipients')
      .select(`
        *,
        letters (*)
      `)
      .eq('recipient_id', recipientId);

    if (error) throw error;

    return (data || []).map(r => ({
      ...r.letters,
      ...r,
      letter_id: r.letters?.id
    })).filter(l => l.id);
  } catch (error) {
    console.error("Error fetching letters by recipient:", error);
    return [];
  }
};

export const deleteLetterRecord = async (id) => {
  try {
    // Rely on CASCADE delete in Supabase or delete recipients first
    const { error: recError } = await supabase
      .from('letter_recipients')
      .delete()
      .eq('letter_id', id);
      
    if (recError) throw recError;

    const { error } = await supabase
      .from('letters')
      .delete()
      .eq('id', id);

    if (error) throw error;
    return true;
  } catch (error) {
    console.error("Error deleting letter record:", error);
    throw error;
  }
};

export const deleteMultipleLetters = async (ids) => {
  if (!ids || !ids.length) return true;
  try {
    const { error: recError } = await supabase
      .from('letter_recipients')
      .delete()
      .in('letter_id', ids);
      
    if (recError) throw recError;

    const { error } = await supabase
      .from('letters')
      .delete()
      .in('id', ids);

    if (error) throw error;
    return true;
  } catch (error) {
    console.error("Error deleting multiple letters:", error);
    throw error;
  }
};

export const createLetterRecord = async (data) => {
  try {
    const { data: record, error } = await supabase
      .from('letters')
      .insert([data])
      .select()
      .single();

    if (error) throw error;
    return record;
  } catch (error) {
    console.error("Error creating letter record:", error);
    throw error;
  }
};

export const updateLetterRecord = async (id, data) => {
  try {
    const { data: record, error } = await supabase
      .from('letters')
      .update(data)
      .eq('id', id)
      .select()
      .single();

    if (error) throw error;
    return record;
  } catch (error) {
    console.error("Error updating letter record:", error);
    throw error;
  }
};

export const sendLetters = async (letterData) => {
  try {
    const { data, error } = await supabase
      .from('letters')
      .insert([letterData])
      .select()
      .single();

    if (error) throw error;

    return { data, error: null };
  } catch (error) {
    console.error("Error sending letters:", error);
    return { data: null, error };
  }
};