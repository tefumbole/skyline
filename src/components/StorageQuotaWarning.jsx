import React, { useState, useEffect } from 'react';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Database, AlertTriangle, CheckCircle, RefreshCcw } from 'lucide-react';
import { getStorageUsage, generateCleanupRecommendations } from '@/utils/storageOptimization';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const StorageQuotaWarning = ({ className, compact = false }) => {
  const [usage, setUsage] = useState(null);
  const [loading, setLoading] = useState(true);

  const checkStorage = async () => {
    setLoading(true);
    try {
      const data = await getStorageUsage();
      setUsage(data);
    } catch (error) {
      console.error("Failed to check storage quota", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    checkStorage();
  }, []);

  if (loading) {
    return (
      <div className={cn("text-xs text-gray-500 flex items-center gap-2", className)}>
        <RefreshCcw className="w-3 h-3 animate-spin" /> Checking storage...
      </div>
    );
  }

  if (!usage) return null;

  const isWarning = usage.percentage >= 80;
  const isCritical = usage.percentage >= 90;
  const recommendations = generateCleanupRecommendations(usage);

  if (compact) {
    return (
        <div className={cn("flex items-center gap-2 text-xs", className)}>
             <Database className={cn("w-3 h-3", isCritical ? "text-red-500" : isWarning ? "text-amber-500" : "text-gray-400")} />
             <span className={cn(isCritical ? "text-red-600 font-bold" : "text-gray-600")}>
                {usage.percentage.toFixed(1)}% Used
             </span>
        </div>
    );
  }

  return (
    <div className={cn("space-y-3", className)}>
      <div className="flex items-center justify-between mb-1">
        <h4 className="text-sm font-semibold flex items-center gap-2 text-gray-700">
          <Database className="w-4 h-4" /> Storage Usage
        </h4>
        <span className="text-xs text-gray-500">{usage.formattedUsed} / {usage.formattedLimit}</span>
      </div>
      
      <Progress 
        value={usage.percentage} 
        className={cn("h-2", isCritical ? "bg-red-100 [&>div]:bg-red-600" : isWarning ? "bg-amber-100 [&>div]:bg-amber-500" : "bg-blue-50 [&>div]:bg-blue-500")}
      />
      
      {isWarning && (
        <Alert variant={isCritical ? "destructive" : "default"} className={cn("mt-4", isWarning && !isCritical ? "border-amber-200 bg-amber-50 text-amber-800" : "")}>
          <AlertTriangle className="h-4 w-4" />
          <AlertTitle>{isCritical ? "Critical Storage Alert" : "Storage Warning"}</AlertTitle>
          <AlertDescription>
            You have used {usage.percentage.toFixed(1)}% of your storage quota.
            {recommendations.length > 0 && (
                <ul className="mt-2 list-disc list-inside text-xs">
                    {recommendations.map((rec, idx) => (
                        <li key={idx}>{rec.action}</li>
                    ))}
                </ul>
            )}
          </AlertDescription>
        </Alert>
      )}

      {!isWarning && (
         <div className="flex items-center gap-2 text-xs text-green-600 bg-green-50 px-3 py-2 rounded border border-green-100">
             <CheckCircle className="w-3 h-3" />
             Storage levels are healthy.
         </div>
      )}
    </div>
  );
};

export default StorageQuotaWarning;