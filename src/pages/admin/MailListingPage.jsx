import React, { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { fetchMessages, fetchMessageCategories } from '@/services/mailListingService';
import MessageDetailsModal from '@/components/admin/MessageDetailsModal';
import { format } from 'date-fns';
import { 
  Search, X, Loader2, Mail, MessageSquare, 
  ChevronLeft, ChevronRight, Eye, CalendarClock, AlertCircle, CheckCircle, RefreshCw
} from 'lucide-react';

const MailListingPage = () => {
  const { toast } = useToast();
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [totalCount, setTotalCount] = useState(0);
  const [categories, setCategories] = useState([]);
  
  // Pagination & Filters
  const [page, setPage] = useState(1);
  const pageSize = 10;
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('All');
  const [categoryFilter, setCategoryFilter] = useState('All');

  const [selectedMessageId, setSelectedMessageId] = useState(null);

  useEffect(() => {
    loadCategories();
  }, []);

  useEffect(() => {
    loadMessages();
  }, [page, statusFilter, categoryFilter]);

  // Debounced search
  useEffect(() => {
    const timer = setTimeout(() => {
      if (page === 1) {
        loadMessages();
      } else {
        setPage(1); 
      }
    }, 500);
    return () => clearTimeout(timer);
  }, [search]);

  const loadCategories = async () => {
    const cats = await fetchMessageCategories();
    setCategories(cats);
  };

  const loadMessages = async () => {
    setLoading(true);
    try {
      const { data, count } = await fetchMessages(page, pageSize, {
        search,
        status: statusFilter,
        category: categoryFilter
      });
      setMessages(data || []);
      setTotalCount(count || 0);
    } catch (error) {
      toast({
        title: "Error loading messages",
        description: error.message,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const clearFilters = () => {
    setSearch('');
    setStatusFilter('All');
    setCategoryFilter('All');
    setPage(1);
  };

  const totalPages = Math.ceil(totalCount / pageSize);

  const getStatusBadge = (status) => {
    switch (status) {
      case 'sent': case 'completed': return <Badge className="bg-green-100 text-green-800 border-green-200"><CheckCircle className="w-3 h-3 mr-1"/> Sent</Badge>;
      case 'delivered': return <Badge className="bg-emerald-100 text-emerald-800 border-emerald-200"><CheckCircle className="w-3 h-3 mr-1"/> Delivered</Badge>;
      case 'failed': return <Badge className="bg-red-100 text-red-800 border-red-200"><AlertCircle className="w-3 h-3 mr-1"/> Failed</Badge>;
      case 'sending': case 'processing': return <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200"><RefreshCw className="w-3 h-3 mr-1 animate-spin"/> Sending</Badge>;
      case 'scheduled': return <Badge className="bg-purple-100 text-purple-800 border-purple-200"><CalendarClock className="w-3 h-3 mr-1"/> Scheduled</Badge>;
      default: return <Badge variant="outline" className="capitalize">{status}</Badge>;
    }
  };

  return (
    <div className="max-w-7xl mx-auto space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Mail Listing</h1>
          <p className="text-gray-500">View and manage all outgoing communications.</p>
        </div>
      </div>

      <Card>
        <CardHeader className="pb-4 border-b">
          <div className="flex flex-col md:flex-row gap-4 items-end md:items-center justify-between">
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
              <Input
                placeholder="Search subject or reference..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9 bg-white"
              />
            </div>
            <div className="flex gap-2 w-full md:w-auto overflow-x-auto">
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-[140px] bg-white">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="All">All Statuses</SelectItem>
                  <SelectItem value="draft">Draft</SelectItem>
                  <SelectItem value="scheduled">Scheduled</SelectItem>
                  <SelectItem value="sending">Sending</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                  <SelectItem value="delivered">Delivered</SelectItem>
                  <SelectItem value="failed">Failed</SelectItem>
                </SelectContent>
              </Select>
              
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger className="w-[150px] bg-white">
                  <SelectValue placeholder="Category" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="All">All Categories</SelectItem>
                  {categories.map(c => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                </SelectContent>
              </Select>

              <Button variant="ghost" onClick={clearFilters} className="px-3" title="Clear Filters">
                <X className="w-4 h-4" />
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex justify-center items-center py-16">
              <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
            </div>
          ) : messages.length === 0 ? (
            <div className="text-center py-16 text-gray-500">
              <Mail className="w-12 h-12 mx-auto text-gray-300 mb-3" />
              <p>No messages found matching your criteria.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader className="bg-gray-50">
                  <TableRow>
                    <TableHead>Reference / Subject</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead className="text-center">Recipients</TableHead>
                    <TableHead>Channels</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead className="text-right">Action</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {messages.map((msg) => (
                    <TableRow key={msg.id} className="hover:bg-gray-50/50 transition-colors cursor-pointer" onClick={() => setSelectedMessageId(msg.id)}>
                      <TableCell>
                        <div className="flex flex-col space-y-0.5">
                          <span className="text-xs font-mono text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded w-fit">
                            {msg.reference}
                          </span>
                          <span className="font-semibold text-gray-900 truncate max-w-[300px]">
                            {msg.subject}
                          </span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="secondary" className="font-normal">{msg.category || 'General'}</Badge>
                      </TableCell>
                      <TableCell className="text-center">
                        <span className="font-medium">{msg.recipients_count}</span>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-1.5">
                          {msg.send_email && <Mail className="w-4 h-4 text-blue-500" title="Email" />}
                          {msg.send_whatsapp && <MessageSquare className="w-4 h-4 text-green-500" title="WhatsApp" />}
                          {!msg.send_email && !msg.send_whatsapp && <span className="text-xs text-gray-400">None</span>}
                        </div>
                      </TableCell>
                      <TableCell>
                        {getStatusBadge(msg.status)}
                      </TableCell>
                      <TableCell className="text-sm text-gray-500">
                        {format(new Date(msg.created_at), 'MMM dd, yyyy')}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); setSelectedMessageId(msg.id); }}>
                          <Eye className="w-4 h-4 text-gray-500" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
          
          {/* Pagination */}
          {!loading && totalPages > 1 && (
            <div className="flex items-center justify-between px-4 py-3 border-t bg-gray-50/50">
              <span className="text-sm text-gray-500">
                Showing {(page - 1) * pageSize + 1} to {Math.min(page * pageSize, totalCount)} of {totalCount}
              </span>
              <div className="flex gap-2">
                <Button 
                  variant="outline" 
                  size="sm" 
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                  disabled={page === 1}
                >
                  <ChevronLeft className="w-4 h-4 mr-1" /> Prev
                </Button>
                <div className="flex items-center px-3 text-sm font-medium">
                  {page} / {totalPages}
                </div>
                <Button 
                  variant="outline" 
                  size="sm" 
                  onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                  disabled={page === totalPages}
                >
                  Next <ChevronRight className="w-4 h-4 ml-1" />
                </Button>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      <MessageDetailsModal 
        isOpen={!!selectedMessageId} 
        onClose={() => setSelectedMessageId(null)} 
        messageId={selectedMessageId} 
      />
    </div>
  );
};

export default MailListingPage;