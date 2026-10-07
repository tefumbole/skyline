/**
 * Utility for analyzing and optimizing Supabase storage usage.
 */
import { supabase } from '@/lib/customSupabaseClient';
import { formatBytes } from '@/utils/imageCompression';

const STORAGE_LIMIT_GB = 1; // Free tier limit is usually 1GB for Supabase (adjust as needed)
const STORAGE_LIMIT_BYTES = STORAGE_LIMIT_GB * 1024 * 1024 * 1024;
const WARNING_THRESHOLD = 0.8; // 80%

/**
 * Checks current storage usage by listing files in known buckets.
 * Note: This is an estimation as Supabase doesn't provide a direct "get total usage" API for client-side easily without admin rights or edge functions.
 * We will scan the most active buckets: 'members-photos', 'shareholder-signatures', 'shareholder-pdfs'.
 */
export const getStorageUsage = async () => {
  const buckets = ['members-photos', 'shareholder-signatures', 'shareholder-pdfs'];
  let totalBytes = 0;
  let fileCount = 0;
  let largeFiles = [];

  for (const bucket of buckets) {
    try {
      // List all files (pagination might be needed for huge buckets, but this is a reasonable start)
      const { data, error } = await supabase.storage.from(bucket).list('', { limit: 1000 });
      
      if (!error && data) {
        data.forEach(file => {
          if (file.metadata) {
            totalBytes += file.metadata.size;
            fileCount++;
            
            // Flag files larger than 2MB
            if (file.metadata.size > 2 * 1024 * 1024) {
              largeFiles.push({
                name: file.name,
                bucket: bucket,
                size: file.metadata.size,
                sizeFormatted: formatBytes(file.metadata.size)
              });
            }
          }
        });
      }
    } catch (e) {
      console.warn(`Failed to scan bucket ${bucket}`, e);
    }
  }

  return {
    usedBytes: totalBytes,
    formattedUsed: formatBytes(totalBytes),
    formattedLimit: formatBytes(STORAGE_LIMIT_BYTES),
    percentage: (totalBytes / STORAGE_LIMIT_BYTES) * 100,
    fileCount,
    largeFiles
  };
};

/**
 * Identifies large files that could be candidates for optimization or deletion.
 */
export const identifyLargeFiles = async () => {
  const usage = await getStorageUsage();
  return usage.largeFiles;
};

/**
 * Calculates storage percentage and returns status.
 */
export const calculateStoragePercentage = (usedBytes) => {
  const percent = (usedBytes / STORAGE_LIMIT_BYTES) * 100;
  let status = 'good';
  
  if (percent >= 90) status = 'critical';
  else if (percent >= 80) status = 'warning';
  
  return {
    percentage: percent.toFixed(2),
    status
  };
};

/**
 * Generates recommendations based on usage analysis.
 */
export const generateCleanupRecommendations = (usageData) => {
  const recommendations = [];

  if (usageData.percentage > 80) {
    recommendations.push({
      priority: 'high',
      title: 'Approaching Storage Limit',
      action: 'Delete old backup files or unused member photos.'
    });
  }

  if (usageData.largeFiles.length > 0) {
    recommendations.push({
      priority: 'medium',
      title: `${usageData.largeFiles.length} Large Files Detected`,
      action: 'Consider compressing or removing files larger than 2MB.'
    });
  }

  if (usageData.usedBytes === 0) {
    recommendations.push({
      priority: 'low',
      title: 'Storage is Empty',
      action: 'System is clean.'
    });
  } else if (recommendations.length === 0) {
    recommendations.push({
      priority: 'low',
      title: 'Storage Healthy',
      action: 'Routine maintenance only.'
    });
  }

  return recommendations;
};

export { formatBytes };