import { supabase } from '@/lib/customSupabaseClient';

/**
 * Account Creation Service
 * IMPORTANT: This service NO LONGER creates Auth users from the frontend.
 * Auth user creation must be done from backend/admin panel only.
 * 
 * This file is kept for backward compatibility and future backend integration.
 */

/**
 * Extract phone number without country code
 * Used for phone number formatting
 */
export const extractPhoneWithoutCode = (countryCode, phoneNumber) => {
  if (!phoneNumber) return '';
  
  // Remove all non-digit characters
  let cleaned = phoneNumber.replace(/\D/g, '');
  
  // Remove leading zeros
  cleaned = cleaned.replace(/^0+/, '');
  
  // If country code is somehow included in the phone number, remove it
  if (countryCode) {
    const codeDigits = countryCode.replace(/\D/g, '');
    if (cleaned.startsWith(codeDigits)) {
      cleaned = cleaned.substring(codeDigits.length);
    }
  }
  
  return cleaned;
};

/**
 * Link booking to user (for future use when backend creates user)
 * Only call this from backend/admin when explicitly creating user
 * @param {string} bookingId - Shareholder booking ID
 * @param {string} userId - User account ID
 * @returns {Promise<boolean>}
 */
export const linkBookingToUser = async (bookingId, userId) => {
  try {
    if (!bookingId || !userId) {
      console.error('linkBookingToUser: Missing bookingId or userId');
      return false;
    }

    console.log('linkBookingToUser: Linking booking', bookingId, 'to user', userId);

    const { error } = await supabase
      .from('shareholders')
      .update({ member_id: userId })
      .eq('id', bookingId);

    if (error) {
      console.error('linkBookingToUser: Error linking booking:', error);
      return false;
    }

    console.log('linkBookingToUser: Successfully linked booking to user');
    return true;
  } catch (err) {
    console.error('linkBookingToUser: Unexpected error:', err);
    return false;
  }
};

/**
 * DEPRECATED: Do not use from frontend
 * Auth user creation must be done from backend only
 * This function is kept for reference but should never be called
 * 
 * @deprecated Use backend API instead
 */
export const getOrCreateUser = async (email, password) => {
  console.warn('getOrCreateUser is DEPRECATED. Auth user creation must be done from backend.');
  return { 
    success: false, 
    userId: null, 
    isNew: false,
    error: 'Auth user creation is not allowed from frontend. Use backend API instead.' 
  };
};

/**
 * DEPRECATED: Do not use from frontend
 * @deprecated Use backend API instead
 */
export const createUserAccount = async (userData) => {
  console.warn('createUserAccount is DEPRECATED. Auth user creation must be done from backend.');
  return { 
    success: false, 
    userId: null, 
    error: 'Auth user creation is not allowed from frontend. Use backend API instead.' 
  };
};

/**
 * DEPRECATED: Do not use from frontend
 * @deprecated Use backend API instead
 */
export const checkUserExists = async (email) => {
  console.warn('checkUserExists is DEPRECATED. Use backend API instead.');
  return { 
    exists: false, 
    userId: null,
    error: 'User checking is not allowed from frontend. Use backend API instead.' 
  };
};

/**
 * DEPRECATED: Do not use from frontend
 * @deprecated Use backend API instead
 */
export const getUserIdByEmail = async (email) => {
  console.warn('getUserIdByEmail is DEPRECATED. Use backend API instead.');
  return null;
};

/**
 * DEPRECATED: Do not use from frontend
 * @deprecated Use backend API instead
 */
export const createAccountAndLinkBooking = async (params) => {
  console.warn('createAccountAndLinkBooking is DEPRECATED. Use backend API instead.');
  return { 
    success: false, 
    userId: null,
    error: 'Account creation is not allowed from frontend. Use backend API instead.' 
  };
};

// Export all functions for backward compatibility
export default {
  extractPhoneWithoutCode,
  linkBookingToUser,
  getOrCreateUser,
  createUserAccount,
  checkUserExists,
  getUserIdByEmail,
  createAccountAndLinkBooking
};