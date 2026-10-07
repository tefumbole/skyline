import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/use-toast';
import { Play, Square, RefreshCw, Loader2, Clock, CheckCircle, AlertCircle, Activity } from 'lucide-react';
import { 
  getQueueStats, 
  processAllPendingJobs, 
  startBackgroundWorker, 
  stopBackgroundWorker 
} from '@/services/queueWorkerService';

const QueueMonitor = () => {
  const { toast } = useToast();
  const [stats, setStats] = useState({ pending: 0, processing: 0, completed: 0, failed: 0, total: 0 });
  const [isWorkerRunning, setIsWorkerRunning] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [loadingStats, setLoadingStats] = useState(true);

  const fetchStats = async () => {
    const currentStats = await getQueueStats();
    setStats(currentStats);
    setLoadingStats(false);
  };

  useEffect(() => {
    fetchStats();
    // Auto-refresh stats every 5 seconds
    const interval = setInterval(fetchStats, 5000);
    return () => clearInterval(interval);
  }, []);

  const handleToggleWorker = () => {
    if (isWorkerRunning) {
      stopBackgroundWorker();
      setIsWorkerRunning(false);
      toast({ title: "Background Worker Stopped" });
    } else {
      startBackgroundWorker();
      setIsWorkerRunning(true);
      toast({ title: "Background Worker Started", description: "Automated queue processing is active." });
    }
  };

  const handleManualProcess = async () => {
    if (isProcessing) return;
    setIsProcessing(true);
    try {
      const count = await processAllPendingJobs();
      toast({ 
        title: "Processing Completed", 
        description: `Successfully processed ${count} pending job(s).` 
      });
      fetchStats();
    } catch (error) {
      toast({ 
        title: "Processing Error", 
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const StatBox = ({ title, value, icon: Icon, colorClass }) => (
    <div className={`p-4 rounded-lg border flex items-center justify-between ${colorClass}`}>
      <div>
        <p className="text-xs font-medium text-gray-500 uppercase">{title}</p>
        <p className="text-2xl font-bold mt-1">{loadingStats ? '-' : value}</p>
      </div>
      <div className="p-3 rounded-full bg-white/50 backdrop-blur-sm">
        <Icon className="w-5 h-5 opacity-80" />
      </div>
    </div>
  );

  return (
    <Card className="shadow-sm border-t-4 border-t-[#003D82]">
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-lg flex items-center">
          <Activity className="w-5 h-5 mr-2 text-[#003D82]" />
          Queue Monitor
        </CardTitle>
        <div className="flex gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            onClick={fetchStats}
            disabled={loadingStats}
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${loadingStats ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
          <Button 
            variant={isWorkerRunning ? "destructive" : "default"} 
            size="sm" 
            onClick={handleToggleWorker}
            className={!isWorkerRunning ? "bg-green-600 hover:bg-green-700 text-white" : ""}
          >
            {isWorkerRunning ? <Square className="w-4 h-4 mr-2" /> : <Play className="w-4 h-4 mr-2" />}
            {isWorkerRunning ? 'Stop Worker' : 'Start Worker'}
          </Button>
          <Button 
            size="sm" 
            onClick={handleManualProcess}
            disabled={isProcessing || stats.pending === 0}
            className="bg-[#003D82] text-white hover:bg-[#002a5a]"
          >
            {isProcessing ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Play className="w-4 h-4 mr-2" />}
            Process Pending
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mt-2">
          <StatBox title="Total" value={stats.total} icon={Activity} colorClass="bg-gray-50 text-gray-800" />
          <StatBox title="Pending" value={stats.pending} icon={Clock} colorClass="bg-blue-50 text-blue-800 border-blue-100" />
          <StatBox title="Processing" value={stats.processing} icon={RefreshCw} colorClass="bg-yellow-50 text-yellow-800 border-yellow-100" />
          <StatBox title="Completed" value={stats.completed} icon={CheckCircle} colorClass="bg-green-50 text-green-800 border-green-100" />
          <StatBox title="Failed" value={stats.failed} icon={AlertCircle} colorClass="bg-red-50 text-red-800 border-red-100" />
        </div>
      </CardContent>
    </Card>
  );
};

export default QueueMonitor;