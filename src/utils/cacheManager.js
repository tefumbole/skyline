import { logInfo, logError } from '@/utils/debug';

/**
 * Clears all data from localStorage.
 */
export const clearLocalStorage = () => {
  try {
    localStorage.clear();
    logInfo('cacheManager', 'clearLocalStorage', 'localStorage cleared successfully.');
    return true;
  } catch (error) {
    logError('cacheManager', 'clearLocalStorage', error);
    return false;
  }
};

/**
 * Clears all data from sessionStorage.
 */
export const clearSessionStorage = () => {
  try {
    sessionStorage.clear();
    logInfo('cacheManager', 'clearSessionStorage', 'sessionStorage cleared successfully.');
    return true;
  } catch (error) {
    logError('cacheManager', 'clearSessionStorage', error);
    return false;
  }
};

/**
 * Attempts to clear the browser's disk cache by unregistering service workers.
 * Note: This has limitations and might not clear everything.
 */
export const clearServiceWorkerCache = async () => {
  if ('serviceWorker' in navigator) {
    try {
      const registrations = await navigator.serviceWorker.getRegistrations();
      if (registrations.length) {
        for (const registration of registrations) {
          await registration.unregister();
          logInfo('cacheManager', 'clearServiceWorkerCache', `Service worker for ${registration.scope} unregistered.`);
        }
      } else {
        logInfo('cacheManager', 'clearServiceWorkerCache', 'No service workers found to unregister.');
      }
      return true;
    } catch (error) {
      logError('cacheManager', 'clearServiceWorkerCache', error);
      return false;
    }
  } else {
    logInfo('cacheManager', 'clearServiceWorkerCache', 'Service workers are not supported in this browser.');
    return true; // Not an error, just not applicable
  }
};


/**
 * A more comprehensive (but potentially disruptive) cache clearing method.
 * It forces a hard reload, which tells the browser to bypass its cache for the request.
 */
export const clearBrowserCache = () => {
    try {
        logInfo('cacheManager', 'clearBrowserCache', 'Forcing a hard reload to clear browser cache.');
        // The `true` parameter forces a reload from the server, bypassing the cache.
        window.location.reload(true);
        return true;
    } catch (error) {
        logError('cacheManager', 'clearBrowserCache', error);
        return false;
    }
}


/**
 * Runs all cache clearing operations sequentially.
 * @returns {Promise<Object>} A report of the clearing operations.
 */
export const clearAllCache = async () => {
  logInfo('cacheManager', 'clearAllCache', 'Starting all cache clearing operations.');
  const report = {
    localStorage: clearLocalStorage(),
    sessionStorage: clearSessionStorage(),
    serviceWorkers: await clearServiceWorkerCache(),
    browserCacheReload: false, // This will happen last
  };
  
  // The browser cache clear is the most disruptive, so we do it at the very end.
  // We add a small delay to ensure other operations complete.
  setTimeout(() => {
    report.browserCacheReload = clearBrowserCache();
  }, 500);

  return report;
};