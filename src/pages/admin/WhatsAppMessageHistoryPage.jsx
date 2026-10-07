import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Search, Download, MessageSquare, CheckCircle, XCircle } from 'lucide-react';
import { saveAs } from 'file-saver';

const WhatsAppMessageHistoryPage = () => {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [typeFilter, setTypeFilter] = useState('all');
  const [selectedLog, setSelectedLog] = useState(null);
  
  const { toast } = useToast();

  const fetchLogs = async () => {
    setLoading(true);
    try {
      let query = supabase
        .from('whatsapp_message_logs')
        .select('*')
        .order('created_at', { ascending: false });

      if (statusFilter !== 'all') query = query.eq('status', statusFilter);
      if (typeFilter !== 'all') query = query.eq('message_type', typeFilter);
      if (searchTerm) {
          query = query.or(`phone_number.ilike.%${searchTerm}%,recipient_name.ilike.%${searchTerm}%`);
      }

      const { data, error } = await query;
      if (error) throw error;
      setLogs(data);
    } catch (error) {
      toast({ title: "Error", description: "Failed to load message history", variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [statusFilter, typeFilter]);

  const handleSearch = (e) => {
      e.preventDefault();
      fetchLogs();
  };

  const exportCSV = () => {
      if (logs.length === 0) return;
      const headers = ['Date', 'Phone', 'Recipient', 'Type', 'Status', 'Message', 'Error'];
      const csvContent = [
          headers.join(','),
          ...logs.map(log => [
              new Date(log.created_at).toLocaleString(),
              log.phone_number,
              `"${log.recipient_name || ''}"`,
              log.message_type,
              log.status,
              `"${(log.message_content || '').replace(/"/g, '""')}"`,
              `"${(log.error_message || '').replace(/"/g, '""')}"`
          ].join(','))
      ].join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      saveAs(blob, `whatsapp_logs_${new Date().toISOString().split('T')[0]}.csv`);
  };

  const getStatusBadge = (status) => {
      switch(status) {
          case 'sent': return <Badge className="bg-green-600">Sent</Badge>;
          case 'failed': return <Badge variant="destructive">Failed</Badge>;
          default: return <Badge variant="outline" className="text-gray-500">Pending</Badge>;
      }
  };

  return (
    <div className="p-6 space-y-6 animate-in fade-in">
        <div className="flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div>
                <h1 className="text-3xl font-bold text-[#003D82] flex items-center gap-2">
                    <MessageSquare className="w-8 h-8" /> WhatsApp History
                </h1>
                <p className="text-gray-500">Audit log of all sent WhatsApp communications.</p>
            </div>
            <Button onClick={exportCSV} variant="outline" className="border-green-600 text-green-700 hover:bg-green-50">
                <Download className="w-4 h-4 mr-2" /> Export CSV
            </Button>
        </div>

        <Card>
            <CardHeader className="pb-3">
                <div className="flex flex-col md:flex-row gap-4 justify-between">
                    <form onSubmit={handleSearch} className="flex gap-2 w-full md:w-auto">
                        <div className="relative w-full md:w-64">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
                            <Input 
                                placeholder="Search phone or name..." 
                                className="pl-8" 
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                        <Button type="submit">Search</Button>
                    </form>
                    
                    <div className="flex gap-2 w-full md:w-auto">
                        <Select value={typeFilter} onValueChange={setTypeFilter}>
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Filter by Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem value="rejection">Rejection</SelectItem>
                                <SelectItem value="approval">Approval</SelectItem>
                                <SelectItem value="announcement">Announcement</SelectItem>
                                <SelectItem value="job_posting">Job Posting</SelectItem>
                                <SelectItem value="interview_scheduled">Interview</SelectItem>
                                <SelectItem value="deadline_reminder">Reminder</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={statusFilter} onValueChange={setStatusFilter}>
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="Filter by Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="sent">Sent</SelectItem>
                                <SelectItem value="failed">Failed</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="flex justify-center p-12"><Loader2 className="w-8 h-8 animate-spin text-blue-600" /></div>
                ) : logs.length === 0 ? (
                    <div className="text-center p-12 text-gray-500">No message logs found matching criteria.</div>
                ) : (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-gray-50">
                                    <TableHead>Time</TableHead>
                                    <TableHead>Recipient</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Preview</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.map((log) => (
                                    <TableRow key={log.id} className="hover:bg-gray-50 cursor-pointer" onClick={() => setSelectedLog(log)}>
                                        <TableCell className="whitespace-nowrap text-xs text-gray-500">
                                            {new Date(log.created_at).toLocaleString()}
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium text-sm">{log.recipient_name || 'Unknown'}</div>
                                            <div className="text-xs text-gray-400">{log.phone_number}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="text-xs font-normal">
                                                {log.message_type.replace('_', ' ')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-[200px] truncate text-sm text-gray-600">
                                            {log.message_content}
                                        </TableCell>
                                        <TableCell>
                                            {getStatusBadge(log.status)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); setSelectedLog(log); }}>
                                                View
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>

        {/* Detail Modal */}
        <Dialog open={!!selectedLog} onOpenChange={() => setSelectedLog(null)}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Message Details</DialogTitle>
                    <DialogDescription>
                        Sent on {selectedLog && new Date(selectedLog.created_at).toLocaleString()}
                    </DialogDescription>
                </DialogHeader>
                {selectedLog && (
                    <div className="space-y-4 py-2">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="text-gray-500 block text-xs uppercase">Recipient</span>
                                <span className="font-medium">{selectedLog.recipient_name}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block text-xs uppercase">Phone</span>
                                <span className="font-medium">{selectedLog.phone_number}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block text-xs uppercase">Type</span>
                                <span className="font-medium capitalize">{selectedLog.message_type.replace('_', ' ')}</span>
                            </div>
                            <div>
                                <span className="text-gray-500 block text-xs uppercase">Status</span>
                                {getStatusBadge(selectedLog.status)}
                            </div>
                        </div>
                        
                        <div className="bg-gray-50 p-4 rounded-md border text-sm text-gray-800 whitespace-pre-wrap">
                            {selectedLog.message_content}
                        </div>

                        {selectedLog.status === 'failed' && (
                            <div className="bg-red-50 p-3 rounded-md border border-red-100 text-sm text-red-700 flex items-start gap-2">
                                <XCircle className="w-4 h-4 mt-0.5 shrink-0" />
                                <div>
                                    <span className="font-semibold block">Error:</span>
                                    {selectedLog.error_message}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    </div>
  );
};

export default WhatsAppMessageHistoryPage;