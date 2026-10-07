import { supabase } from '@/lib/customSupabaseClient';
import { clearAllCache } from '@/utils/cacheManager';
import { runFullCleanup } from '@/services/databaseCleanupService';

/**
 * Service to handle full system maintenance tasks
 */

/**
 * Runs the full maintenance suite: DB cleanup and local cache clearing.
 * @returns {Promise<Object>} Result status
 */
export const runFullMaintenance = async () => {
  try {
    // 1. Run Database Cleanup
    const cleanupResult = await runFullCleanup();
    
    // 2. Clear Application Cache via Utility
    await clearAllCache();

    // 3. Clear Specific LocalStorage Items manually (redundancy)
    localStorage.removeItem('membersList');
    localStorage.removeItem('systemSettings');

    // 4. Return success report
    return {
      success: true,
      message: 'Maintenance completed successfully',
      details: cleanupResult
    };

  } catch (error) {
    console.error("Maintenance Service Error:", error);
    
    // Provide user-friendly error messages based on the error
    let userMessage = "System maintenance failed. Please try again.";
    
    if (error.message && error.message.includes('Network')) {
      userMessage = "Network error during maintenance. Please check your connection.";
    }

    throw new Error(userMessage);
  }
};