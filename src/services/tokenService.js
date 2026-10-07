import { supabase } from '@/lib/customSupabaseClient';

/**
 * Service to handle token validation and management
 */
export const tokenService = {
  /**
   * Checks if the current session's access token is expired or about to expire
   * @param {Object} session - The Supabase session object
   * @param {number} bufferSeconds - Time in seconds to check before actual expiration (default 60s)
   * @returns {boolean}
   */
  isTokenExpired: (session, bufferSeconds = 60) => {
    if (!session?.expires_at) return true;
    const expiresAt = session.expires_at * 1000; // Convert to ms
    const now = Date.now();
    // Check if token expires within the buffer period
    return now > (expiresAt - (bufferSeconds * 1000));
  },

  /**
   * Validates the current session and handles refresh token errors
   * @returns {Promise<{session: Object|null, error: Error|null}>}
   */
  validateSession: async () => {
    try {
      // getSession() automatically handles token refresh if needed
      const { data: { session }, error } = await supabase.auth.getSession();

      if (error) {
        // Handle specific refresh token errors
        if (isRefreshTokenError(error)) {
          console.warn('Refresh token invalid or missing. Clearing session.');
          await supabase.auth.signOut();
          return { session: null, error };
        }
        throw error;
      }

      return { session, error: null };
    } catch (error) {
      console.error('Session validation failed:', error);
      return { session: null, error };
    }
  },

  /**
   * Force refreshes the session
   */
  refreshSession: async () => {
    const { data, error } = await supabase.auth.refreshSession();
    return { session: data.session, error };
  }
};

/**
 * Helper to identify refresh token related errors
 */
function isRefreshTokenError(error) {
  const msg = error?.message?.toLowerCase() || '';
  return (
    msg.includes('refresh_token_not_found') ||
    msg.includes('invalid refresh token') ||
    msg.includes('not logged in') ||
    msg.includes('jwt expired')
  );
}