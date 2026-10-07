import { supabase } from '@/lib/customSupabaseClient';
import { logInfo, logError } from '@/utils/debug';

/**
 * Finds duplicate members based on a combination of name and email.
 * @returns {Promise<Array>} A list of duplicate member groups.
 */
export const findDuplicateMembers = async () => {
  try {
    const { data: members, error } = await supabase.from('members').select('id, name, email, created_at');
    if (error) throw error;

    const memberMap = new Map();
    members.forEach(member => {
      const key = `${member.name}|${member.email}`.toLowerCase();
      if (!memberMap.has(key)) {
        memberMap.set(key, []);
      }
      memberMap.get(key).push(member);
    });

    const duplicates = [];
    for (const group of memberMap.values()) {
      if (group.length > 1) {
        // Sort by creation date to identify the original
        group.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        duplicates.push(group);
      }
    }
    return duplicates;
  } catch (error) {
    logError('databaseCleanupService', 'findDuplicateMembers', error);
    return [];
  }
};

/**
 * Removes duplicate members, keeping the oldest record.
 * @returns {Promise<Object>} A report of removed members.
 */
export const removeDuplicateMembers = async () => {
  const duplicates = await findDuplicateMembers();
  if (duplicates.length === 0) {
    return { removedCount: 0, errors: [] };
  }

  const idsToRemove = [];
  duplicates.forEach(group => {
    // The first element is the oldest, so we keep it.
    // We remove all subsequent elements.
    idsToRemove.push(...group.slice(1).map(member => member.id));
  });

  if (idsToRemove.length === 0) {
    return { removedCount: 0, errors: [] };
  }
  
  logInfo('databaseCleanupService', 'removeDuplicateMembers', `Attempting to remove ${idsToRemove.length} duplicate members.`);

  const { data, error } = await supabase
    .from('members')
    .delete()
    .in('id', idsToRemove);

  if (error) {
    logError('databaseCleanupService', 'removeDuplicateMembers', error);
    return { removedCount: 0, errors: [error.message] };
  }

  return { removedCount: idsToRemove.length, errors: [] };
};

/**
 * Finds and removes records that have broken foreign key relationships.
 * Example: 'announcement_recipients' with a non-existent 'announcement_id'.
 * This is a sample, a real-world version would check many more tables.
 * @returns {Promise<Object>} A report of removed records.
 */
export const removeOrphanedRecords = async () => {
    const report = { removedCount: 0, errors: [] };
    
    try {
        // Example: Find orphaned announcement_recipients
        const { data: recipients, error: recipientsError } = await supabase
            .from('announcement_recipients')
            .select('id, announcement_id');
        
        if (recipientsError) throw recipientsError;

        if (recipients.length > 0) {
            const { data: announcements, error: annError } = await supabase
                .from('announcements')
                .select('id');
            
            if (annError) throw annError;

            const validAnnouncementIds = new Set(announcements.map(a => a.id));
            const orphanedRecipientIds = recipients
                .filter(r => r.announcement_id && !validAnnouncementIds.has(r.announcement_id))
                .map(r => r.id);

            if (orphanedRecipientIds.length > 0) {
                logInfo('databaseCleanupService', 'removeOrphanedRecords', `Found ${orphanedRecipientIds.length} orphaned announcement recipients.`);
                const { error: deleteError } = await supabase
                    .from('announcement_recipients')
                    .delete()
                    .in('id', orphanedRecipientIds);
                
                if (deleteError) {
                    report.errors.push(`Failed to delete orphaned recipients: ${deleteError.message}`);
                } else {
                    report.removedCount += orphanedRecipientIds.length;
                }
            }
        }

    } catch (error) {
        logError('databaseCleanupService', 'removeOrphanedRecords', error);
        report.errors.push(error.message);
    }
    
    return report;
};


/**
 * Runs a full database cleanup process.
 * @returns {Promise<Object>} A detailed report of the cleanup.
 */
export const runFullCleanup = async () => {
  logInfo('databaseCleanupService', 'runFullCleanup', 'Starting full database cleanup.');
  const duplicateReport = await removeDuplicateMembers();
  const orphanReport = await removeOrphanedRecords();

  const fullReport = {
    timestamp: new Date().toISOString(),
    duplicatesRemoved: duplicateReport.removedCount,
    orphansRemoved: orphanReport.removedCount,
    errors: [...duplicateReport.errors, ...orphanReport.errors],
  };

  logInfo('databaseCleanupService', 'runFullCleanup', 'Cleanup finished.', fullReport);
  return fullReport;
};