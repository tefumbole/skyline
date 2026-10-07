import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Search, Trash2, RefreshCw, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { formatPrice } from '@/services/sharePriceService';

const AdminShareHolderListPage = () => {
  const [shareholders, setShareholders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const { toast } = useToast();

  const fetchShareholders = async () => {
    console.log('[ADMIN SHAREHOLDERS] Fetching shareholders list');
    console.log('[ADMIN SHAREHOLDERS] Filter status:', filterStatus);
    
    setLoading(true);
    setError(null);

    try {
      let query = supabase
        .from('shareholders')
        .select('*')
        .order('approved_at', { ascending: false });

      // Apply status filter if not 'all'
      if (filterStatus !== 'all') {
        query = query.eq('status', filterStatus);
        console.log('[ADMIN SHAREHOLDERS] Applying status filter:', filterStatus);
      }

      const { data, error: fetchError } = await query;

      if (fetchError) {
        console.error('[ADMIN SHAREHOLDERS] Fetch error:', fetchError);
        throw fetchError;
      }

      console.log('[ADMIN SHAREHOLDERS] Fetched', data?.length || 0, 'shareholders');
      
      // Guarantee array type
      setShareholders(Array.isArray(data) ? data : []);
      
    } catch (err) {
      console.error('[ADMIN SHAREHOLDERS] Error fetching shareholders:', err);
      setError(err.message || 'Failed to load shareholders');
      // Ensure shareholders is always an array even on error
      setShareholders([]);
      
      toast({
        title: "Error",
        description: err.message || 'Failed to load shareholders',
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchShareholders();
  }, [filterStatus]);

  const handleDelete = async (id) => {
    console.log('[ADMIN SHAREHOLDERS] Attempting to delete shareholder:', id);
    
    if (!window.confirm("Are you sure you want to delete this shareholder record? This action cannot be undone.")) {
      return;
    }

    try {
      const { error: deleteError } = await supabase
        .from('shareholders')
        .delete()
        .eq('id', id);

      if (deleteError) {
        console.error('[ADMIN SHAREHOLDERS] Delete error:', deleteError);
        throw deleteError;
      }

      console.log('[ADMIN SHAREHOLDERS] Shareholder deleted successfully');
      
      toast({
        title: "Success",
        description: "Shareholder record deleted successfully.",
        className: "bg-green-500 text-white"
      });
      
      fetchShareholders();
    } catch (err) {
      console.error('[ADMIN SHAREHOLDERS] Error deleting shareholder:', err);
      
      toast({
        title: "Error",
        description: err.message || 'Failed to delete shareholder record.',
        variant: "destructive"
      });
    }
  };

  const handleRefresh = () => {
    console.log('[ADMIN SHAREHOLDERS] Manual refresh triggered');
    fetchShareholders();
  };

  const handleRetry = () => {
    console.log('[ADMIN SHAREHOLDERS] Retry triggered');
    window.location.reload();
  };

  // Type-safe filtering with optional chaining
  const filteredShareholders = Array.isArray(shareholders)
    ? shareholders.filter((s) => {
        if (!searchTerm.trim()) return true;
        
        const search = searchTerm.toLowerCase();
        return (
          s?.full_name?.toLowerCase().includes(search) ||
          s?.email?.toLowerCase().includes(search) ||
          s?.phone_number?.toLowerCase().includes(search) ||
          s?.full_phone_number?.toLowerCase().includes(search) ||
          s?.country_code?.toLowerCase().includes(search)
        );
      })
    : [];

  const getStatusBadgeVariant = (status) => {
    switch (status) {
      case 'approved':
        return 'default';
      case 'pending_approval':
        return 'secondary';
      case 'rejected':
        return 'destructive';
      default:
        return 'outline';
    }
  };

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'approved':
        return 'bg-green-500 text-white';
      case 'pending_approval':
        return 'bg-yellow-500 text-white';
      case 'rejected':
        return 'bg-red-500 text-white';
      default:
        return '';
    }
  };

  const getPaymentStatusClass = (paymentStatus) => {
    switch (paymentStatus) {
      case 'completed':
      case 'paid':
        return 'bg-green-500 text-white';
      case 'pending':
        return 'bg-yellow-500 text-white';
      case 'failed':
        return 'bg-red-500 text-white';
      default:
        return 'bg-gray-500 text-white';
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    } catch {
      return 'Invalid Date';
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="p-6">
        <div className="flex flex-col items-center justify-center min-h-[400px] space-y-4">
          <Loader2 className="h-12 w-12 animate-spin text-blue-600" />
          <p className="text-gray-600 text-lg">Loading shareholders...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="p-6">
        <Alert variant="destructive" className="max-w-2xl mx-auto">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription className="flex items-center justify-between">
            <span>{error}</span>
            <Button onClick={handleRetry} variant="outline" size="sm">
              Retry
            </Button>
          </AlertDescription>
        </Alert>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Shareholder Management</h1>
          <p className="text-gray-600 mt-1">
            Showing {filteredShareholders.length} of {shareholders.length} shareholders
          </p>
        </div>
        <Button onClick={handleRefresh} variant="outline" className="gap-2">
          <RefreshCw className="h-4 w-4" />
          Refresh
        </Button>
      </div>

      {/* Main Card */}
      <Card>
        <CardHeader>
          <div className="flex flex-col md:flex-row justify-between gap-4">
            <CardTitle>Registered Shareholders</CardTitle>
            <div className="flex flex-col md:flex-row gap-3 w-full md:w-auto">
              {/* Status Filter */}
              <Select value={filterStatus} onValueChange={setFilterStatus}>
                <SelectTrigger className="w-full md:w-48">
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="pending_approval">Pending Approval</SelectItem>
                  <SelectItem value="approved">Approved</SelectItem>
                  <SelectItem value="rejected">Rejected</SelectItem>
                </SelectContent>
              </Select>

              {/* Search */}
              <div className="relative w-full md:w-72">
                <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-500" />
                <Input
                  placeholder="Search name, email, phone..."
                  className="pl-8"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          {/* Empty State - No shareholders at all */}
          {shareholders.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 text-lg">No shareholders found.</p>
              <p className="text-gray-400 text-sm mt-2">
                Shareholders will appear here once they register.
              </p>
            </div>
          ) : filteredShareholders.length === 0 ? (
            /* Empty State - No search results */
            <div className="text-center py-12">
              <p className="text-gray-500 text-lg">No shareholders match your search.</p>
              <p className="text-gray-400 text-sm mt-2">
                Try adjusting your search terms or filters.
              </p>
            </div>
          ) : (
            /* Data Table */
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Contact</TableHead>
                    <TableHead>Country</TableHead>
                    <TableHead>Shares</TableHead>
                    <TableHead>Investment</TableHead>
                    <TableHead>Payment Status</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Approved Date</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredShareholders.map((shareholder) => (
                    <TableRow key={shareholder.id}>
                      {/* Name */}
                      <TableCell className="font-medium">
                        {shareholder?.full_name || 'N/A'}
                      </TableCell>

                      {/* Contact */}
                      <TableCell>
                        <div className="flex flex-col text-sm">
                          <span>{shareholder?.email || 'N/A'}</span>
                          <span className="text-gray-500 text-xs">
                            {shareholder?.full_phone_number || shareholder?.phone_number || 'N/A'}
                          </span>
                        </div>
                      </TableCell>

                      {/* Country */}
                      <TableCell className="text-sm">
                        {shareholder?.country_code || 'N/A'}
                      </TableCell>

                      {/* Shares */}
                      <TableCell className="font-semibold">
                        {shareholder?.shares_assigned || 0}
                      </TableCell>

                      {/* Investment Amount */}
                      <TableCell className="font-semibold text-blue-600">
                        {formatPrice(shareholder?.investment_amount || 0)}
                      </TableCell>

                      {/* Payment Status */}
                      <TableCell>
                        <Badge 
                          variant="outline"
                          className={getPaymentStatusClass(shareholder?.payment_status)}
                        >
                          {shareholder?.payment_status || 'pending'}
                        </Badge>
                      </TableCell>

                      {/* Approval Status */}
                      <TableCell>
                        <Badge 
                          variant={getStatusBadgeVariant(shareholder?.status)}
                          className={getStatusBadgeClass(shareholder?.status)}
                        >
                          {shareholder?.status || 'pending'}
                        </Badge>
                      </TableCell>

                      {/* Date */}
                      <TableCell className="text-sm text-gray-500">
                        {formatDate(shareholder?.approved_at || shareholder?.created_at)}
                      </TableCell>

                      {/* Actions */}
                      <TableCell className="text-right">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleDelete(shareholder.id)}
                          className="text-red-500 hover:text-red-700 hover:bg-red-50"
                        >
                          <Trash2 className="h-4 w-4" />
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
    </div>
  );
};

export default AdminShareHolderListPage;