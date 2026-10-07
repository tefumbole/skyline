import React from 'react';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';

export default function TemplateFavorites() {
  const { toast } = useToast();
  const handleAction = () => toast({ description: "🚧 This feature isn't implemented yet—but don't worry! You can request it in your next prompt! 🚀" });

  return (
    <div className="p-6">
      <h1 className="text-3xl font-bold mb-6">Favorite Templates</h1>
      <div className="text-center p-12 border rounded-lg bg-gray-50">
        <p className="text-gray-500 mb-4">You haven't favorited any templates yet.</p>
        <Button onClick={handleAction}>Browse Library</Button>
      </div>
    </div>
  );
}