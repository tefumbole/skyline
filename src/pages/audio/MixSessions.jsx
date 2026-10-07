import React from 'react';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function MixSessions() {
  const { toast } = useToast();
  const handleAction = () => toast({ description: "🚧 This feature isn't implemented yet—but don't worry! You can request it in your next prompt! 🚀" });

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">My Mix Sessions</h1>
        <Button onClick={handleAction}>New Session</Button>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Session Name</TableHead>
            <TableHead>Artist</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow>
            <TableCell>Summer Hit 2026</TableCell>
            <TableCell>The Band</TableCell>
            <TableCell>Oct 24, 2026</TableCell>
            <TableCell><Button variant="outline" size="sm" onClick={handleAction}>View</Button></TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  );
}