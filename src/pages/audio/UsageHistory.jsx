import React from 'react';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';

export default function UsageHistory() {
  const { toast } = useToast();
  const handleAction = () => toast({ description: "🚧 This feature isn't implemented yet—but don't worry! You can request it in your next prompt! 🚀" });

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">Usage History</h1>
        <Button variant="outline" onClick={handleAction}>Export CSV</Button>
      </div>
      <div className="text-center p-12 border rounded-lg bg-gray-50">
        <p className="text-gray-500">No recent usage history.</p>
      </div>
    </div>
  );
}