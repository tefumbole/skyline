import { supabase } from '@/lib/customSupabaseClient';

/**
 * Session Management Utilities
 * Provides safe session validation and refresh logic
 */

const SUPABASE_STORAGE_KEY = 'alpha_supabase_auth';

/**
 * Validates if a session exists and is valid
 * @returns {Promise<{valid: boolean, session: object|null, error: Error|null}>}
 */
export const validateSession = async () => {
  try {
    // Check if session exists
    const { data: sessionData, error: sessionError } = await supabase.auth.getSession();

    if (sessionError) {
      console.error('sessionUtils: Session validation error:', sessionError);
      return { valid: false, session: null, error: sessionError };
    }

    const session = sessionData?.session;

    // No session exists (user not logged in)
    if (!session) {
      return { valid: false, session: null, error: null };
    }

    // Check if session has required fields
    if (!session.access_token || !session.user) {
      console.warn('sessionUtils: Session missing required fields');
      return { valid: false, session: null, error: new Error('Invalid session structure') };
    }

    // Session is valid
    return { valid: true, session, error: null };

  } catch (err) {
    console.error('sessionUtils: Unexpected error validating session:', err);
    return { valid: false, session: null, error: err };
  }
};

/**
 * Refreshes the session if it's about to expire
 * @returns {Promise<{success: boolean, session: object|null, refreshed: boolean, cleared: boolean}>}
 */
export const refreshSessionIfNeeded = async () => {
  try {
    // Validate current session
    const validation = await validateSession();

    if (!validation.valid) {
      console.log('sessionUtils: No valid session to refresh');
      return { success: true, session: null, refreshed: false, cleared: false };
    }

    const session = validation.session;
    const expiresAt = session.expires_at;

    if (!expiresAt) {
      console.warn('sessionUtils: Session has no expiration time');
      return { success: true, session, refreshed: false, cleared: false };
    }

    // Check if session will expire in next 5 minutes
    const now = Math.floor(Date.now() / 1000);
    const timeUntilExpiry = expiresAt - now;
    const REFRESH_THRESHOLD = 5 * 60; // 5 minutes

    if (timeUntilExpiry > REFRESH_THRESHOLD) {
      // Session is still valid, no refresh needed
      return { success: true, session, refreshed: false, cleared: false };
    }

    console.log('sessionUtils: Session expiring soon, refreshing...');

    // Attempt to refresh session
    const { data: refreshData, error: refreshError } = await supabase.auth.refreshSession();

    if (refreshError) {
      console.error('sessionUtils: Session refresh failed:', refreshError);
      
      // Clear invalid session
      await clearInvalidSession();
      
      return { success: false, session: null, refreshed: false, cleared: true };
    }

    const newSession = refreshData?.session;

    if (!newSession) {
      console.warn('sessionUtils: Session refresh returned no session');
      await clearInvalidSession();
      return { success: false, session: null, refreshed: false, cleared: true };
    }

    console.log('sessionUtils: Session refreshed successfully');
    return { success: true, session: newSession, refreshed: true, cleared: false };

  } catch (err) {
    console.error('sessionUtils: Unexpected error refreshing session:', err);
    await clearInvalidSession();
    return { success: false, session: null, refreshed: false, cleared: true };
  }
};

/**
 * Clears an invalid session from storage
 * @returns {Promise<void>}
 */
export const clearInvalidSession = async () => {
  try {
    console.log('sessionUtils: Clearing invalid session');

    // Sign out from Supabase
    await supabase.auth.signOut();

    // Clear local storage
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        localStorage.removeItem(SUPABASE_STORAGE_KEY);
      } catch (err) {
        console.warn('sessionUtils: Could not clear localStorage:', err);
      }
    }

    console.log('sessionUtils: Invalid session cleared');
  } catch (err) {
    console.error('sessionUtils: Error clearing invalid session:', err);
  }
};

/**
 * Gets a safe user object (checks session validity first)
 * @returns {Promise<{user: object|null, error: Error|null}>}
 */
export const getSafeUser = async () => {
  try {
    const validation = await validateSession();

    if (!validation.valid) {
      return { user: null, error: validation.error };
    }

    const { data: userData, error: userError } = await supabase.auth.getUser();

    if (userError) {
      console.error('sessionUtils: Error getting user:', userError);
      await clearInvalidSession();
      return { user: null, error: userError };
    }

    return { user: userData?.user || null, error: null };

  } catch (err) {
    console.error('sessionUtils: Unexpected error getting safe user:', err);
    await clearInvalidSession();
    return { user: null, error: err };
  }
};

export default {
  validateSession,
  refreshSessionIfNeeded,
  clearInvalidSession,
  getSafeUser
};