import { supabase } from '@/lib/customSupabaseClient';

/**
 * Validates if a session exists AND the corresponding user profile is valid.
 * This helps detect and reject stale or orphaned sessions.
 * 
 * @returns {Promise<{isValid: boolean, session: object|null, profile: object|null}>}
 */
export const validateSessionAndProfile = async () => {
    console.log("[SessionValidator] Step 1: Starting session and profile validation...");
    
    try {
        // 1. Check if a Supabase session exists
        const { data: sessionData, error: sessionError } = await supabase.auth.getSession();
        
        if (sessionError) {
            console.warn("[SessionValidator] Step 2: Session error encountered:", sessionError.message);
            return { isValid: false, session: null, profile: null };
        }
        
        const session = sessionData?.session;
        if (!session) {
            console.log("[SessionValidator] Step 2: No active Supabase session found.");
            return { isValid: false, session: null, profile: null };
        }

        const user = session.user;
        if (!user || !user.id) {
            console.warn("[SessionValidator] Step 3: Session exists but user data is invalid or missing ID.");
            return { isValid: false, session: null, profile: null };
        }
        
        console.log(`[SessionValidator] Step 3: Valid session found for user ID: ${user.id}. Checking profile...`);

        // 2. Query the profiles table to verify the user profile exists
        const { data: profile, error: profileError } = await supabase
            .from('profiles')
            .select('*')
            .eq('id', user.id)
            .single();

        if (profileError) {
            console.warn("[SessionValidator] Step 4: Profile fetch error (may be orphaned session):", profileError.message);
            return { isValid: false, session, profile: null };
        }

        if (!profile) {
            console.warn("[SessionValidator] Step 4: Profile does not exist in the database for this user.");
            return { isValid: false, session, profile: null };
        }

        console.log(`[SessionValidator] Step 5: Success! Session and profile are fully valid. Role: ${profile.role}`);
        
        // 3. Return true only if both session and profile are valid
        return { isValid: true, session, profile };

    } catch (err) {
        console.error("[SessionValidator] Unexpected critical error during validation:", err);
        return { isValid: false, session: null, profile: null };
    }
};