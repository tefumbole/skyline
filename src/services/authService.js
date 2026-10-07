import { supabase } from '@/lib/customSupabaseClient';

/**
 * Auth Service
 * Provides safe authentication check utilities that prevent session_not_found errors
 */

/**
 * Safely gets the current authenticated user
 * Checks session validity before calling getUser() to prevent 403 errors
 * @returns {Promise<{user: object|null, error: Error|null}>}
 */
export const getSafeCurrentUser = async () => {
  try {
    // First, check if a valid session exists
    const { data: sessionData, error: sessionError } = await supabase.auth.getSession();

    if (sessionError) {
      console.error('authService: Session check error:', sessionError);
      return { user: null, error: sessionError };
    }

    // If no session exists, return null user (not an error - just not logged in)
    if (!sessionData?.session) {
      return { user: null, error: null };
    }

    // Session exists, now safe to get user
    const { data: userData, error: userError } = await supabase.auth.getUser();

    if (userError) {
      console.error('authService: Get user error:', userError);
      
      // Clear invalid session
      try {
        await supabase.auth.signOut();
      } catch (signOutError) {
        console.error('authService: Error signing out invalid session:', signOutError);
      }

      return { user: null, error: userError };
    }

    return { user: userData?.user || null, error: null };

  } catch (err) {
    console.error('authService: Unexpected error in getSafeCurrentUser:', err);
    
    // Clear potentially corrupt session
    try {
      await supabase.auth.signOut();
    } catch (signOutError) {
      console.error('authService: Error signing out after error:', signOutError);
    }

    return { user: null, error: err };
  }
};

/**
 * Checks if a user is currently authenticated
 * @returns {Promise<boolean>}
 */
export const isAuthenticated = async () => {
  try {
    const { user, error } = await getSafeCurrentUser();
    return !error && user !== null;
  } catch (err) {
    console.error('authService: Error checking authentication:', err);
    return false;
  }
};

/**
 * Requires authentication and redirects to login if not authenticated
 * Useful for protected routes
 * @param {function} navigate - React Router navigate function
 * @param {string} redirectPath - Optional path to redirect after login
 * @returns {Promise<{authenticated: boolean, user: object|null}>}
 */
export const requireAuth = async (navigate, redirectPath = null) => {
  try {
    const { user, error } = await getSafeCurrentUser();

    if (error || !user) {
      // Store intended destination
      if (redirectPath) {
        sessionStorage.setItem('auth_redirect', redirectPath);
      }

      // Redirect to login
      if (navigate) {
        navigate('/login');
      }

      return { authenticated: false, user: null };
    }

    return { authenticated: true, user };

  } catch (err) {
    console.error('authService: Error in requireAuth:', err);
    
    if (navigate) {
      navigate('/login');
    }

    return { authenticated: false, user: null };
  }
};

/**
 * Gets the user's profile from the profiles table
 * @param {string} userId - User ID
 * @returns {Promise<{profile: object|null, error: Error|null}>}
 */
export const getUserProfile = async (userId) => {
  try {
    if (!userId) {
      return { profile: null, error: new Error('User ID is required') };
    }

    const { data, error } = await supabase
      .from('profiles')
      .select('*')
      .eq('id', userId)
      .single();

    if (error) {
      console.error('authService: Error fetching profile:', error);
      return { profile: null, error };
    }

    return { profile: data, error: null };

  } catch (err) {
    console.error('authService: Unexpected error fetching profile:', err);
    return { profile: null, error: err };
  }
};

/**
 * Checks if the current user has admin role
 * @returns {Promise<boolean>}
 */
export const isAdmin = async () => {
  try {
    const { user, error } = await getSafeCurrentUser();
    
    if (error || !user) {
      return false;
    }

    const { profile } = await getUserProfile(user.id);
    
    if (!profile) {
      return false;
    }

    const role = (profile.role || '').toLowerCase();
    return ['admin', 'super_admin', 'director'].includes(role);

  } catch (err) {
    console.error('authService: Error checking admin status:', err);
    return false;
  }
};

export default {
  getSafeCurrentUser,
  isAuthenticated,
  requireAuth,
  getUserProfile,
  isAdmin
};