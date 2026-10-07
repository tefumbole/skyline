import { supabase } from '@/lib/customSupabaseClient';
import { logInfo, logError, logWarn } from '@/utils/debug';

let monitoringInterval = null;

/**
 * Checks for members with duplicate names or emails.
 * @returns {Promise<Array>} A list of duplicate groups.
 */
async function findDuplicateMembers() {
  try {
    const { data: members, error } = await supabase.from('members').select('name, email');
    if (error) throw error;

    const duplicates = {};
    members.forEach(member => {
      const key = `${member.name}|${member.email}`.toLowerCase();
      if (!duplicates[key]) {
        duplicates[key] = [];
      }
      duplicates[key].push(member);
    });

    return Object.values(duplicates).filter(group => group.length > 1);
  } catch (error) {
    logError('dataConsistencyMonitor', 'findDuplicateMembers', error);
    return [];
  }
}

/**
 * Checks if the current session is valid.
 * @returns {boolean}
 */
function checkSessionValidity() {
  const SESSION_KEY = 'alpha_admin_session_v2';
  try {
    const storedSession = localStorage.getItem(SESSION_KEY);
    if (!storedSession) {
      logWarn('dataConsistencyMonitor', 'checkSessionValidity', 'No local session found.');
      return false;
    }
    const sessionData = JSON.parse(storedSession);
    const isValid = new Date().getTime() < sessionData.expiry;
    if (!isValid) {
      logWarn('dataConsistencyMonitor', 'checkSessionValidity', 'Local session has expired.');
    }
    return isValid;
  } catch (error) {
    logError('dataConsistencyMonitor', 'checkSessionValidity', error);
    return false;
  }
}

/**
 * Manually trigger all consistency checks.
 * @returns {Promise<Object>} A report of the checks.
 */
export async function runManualCheck() {
  logInfo('dataConsistencyMonitor', 'runManualCheck', 'Manual data consistency check initiated.');
  const duplicateMembers = await findDuplicateMembers();
  const sessionValid = checkSessionValidity();

  if (duplicateMembers.length > 0) {
    logWarn('dataConsistencyMonitor', 'runManualCheck', `Found ${duplicateMembers.length} group(s) of duplicate members.`);
  }
  if (!sessionValid) {
    logWarn('dataConsistencyMonitor', 'runManualCheck', 'Session validity check failed.');
  }

  const report = {
    timestamp: new Date().toISOString(),
    checks: [
      {
        name: 'Duplicate Members',
        status: duplicateMembers.length === 0 ? 'OK' : 'Warning',
        details: `${duplicateMembers.length} duplicate groups found.`,
        data: duplicateMembers,
      },
      {
        name: 'Session Validity',
        status: sessionValid ? 'OK' : 'Error',
        details: sessionValid ? 'Session is valid.' : 'Session is invalid or expired.',
      },
    ],
  };

  if (report.checks.every(c => c.status === 'OK')) {
    logInfo('dataConsistencyMonitor', 'runManualCheck', 'All checks passed.');
  }

  return report;
}

/**
 * Starts the periodic monitoring.
 * @param {number} interval - The interval in minutes.
 */
export function startMonitoring(interval = 5) {
  if (monitoringInterval) {
    logInfo('dataConsistencyMonitor', 'startMonitoring', 'Monitoring is already running.');
    return;
  }

  const intervalMs = interval * 60 * 1000;
  logInfo('dataConsistencyMonitor', 'startMonitoring', `Starting data consistency monitor every ${interval} minutes.`);
  
  // Run once immediately
  runManualCheck();

  monitoringInterval = setInterval(async () => {
    await runManualCheck();
  }, intervalMs);
}

/**
 * Stops the periodic monitoring.
 */
export function stopMonitoring() {
  if (monitoringInterval) {
    clearInterval(monitoringInterval);
    monitoringInterval = null;
    logInfo('dataConsistencyMonitor', 'stopMonitoring', 'Data consistency monitor stopped.');
  }
}