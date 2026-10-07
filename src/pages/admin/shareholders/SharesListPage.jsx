import React, { useState, useEffect } from 'react';
import { formatPrice, getSystemSettings } from '@/services/sharePriceService';
import { formatCountryCodeDisplay } from '@/services/countryCodeService';
import { getApprovedShareholders, updateShareholder, deleteShareholder } from '@/services/shareholderService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/components/ui/use-toast';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';
import {
  Loader2,
  Edit,
  Save,
  X,
  Trash2,
  AlertCircle,
  Briefcase,
  DollarSign,
  PieChart,
  CheckCircle
} from 'lucide-react';
import { format } from 'date-fns';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle
} from '@/components/ui/alert-dialog';

const SharesListPage = () => {
  const { toast } = useToast();

  const [loading, setLoading] = useState(true);
  const [shareholders, setShareholders] = useState([]);
  const [error, setError] = useState(null);
  const [editingId, setEditingId] = useState(null);
  const [editedShares, setEditedShares] = useState({});
  const [deleteConfirmId, setDeleteConfirmId] = useState(null);
  const [totalCompanyShares, setTotalCompanyShares] = useState(100);
  const [sharePrice, setSharePrice] = useState(1000);
  const [currency, setCurrency] = useState('USD');

  const fetchData = async () => {
    console.log('[SHARES LIST] Fetching shareholders and settings');
    setLoading(true);
    setError(null);

    try {
      // Fetch system settings for total shares and price
      const settings = await getSystemSettings();
      console.log('[SHARES LIST] Settings loaded:', settings);
      setTotalCompanyShares(settings.total_shares_available || 100);
      setSharePrice(settings.price_per_share || 1000);
      setCurrency(settings.currency || 'USD');

      // Fetch approved shareholders
      const data = await getApprovedShareholders();
      console.log('[SHARES LIST] Fetched', data.length, 'approved shareholders');
      setShareholders(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error('[SHARES LIST] Error fetching data:', err);
      setError(err.message || 'Failed to load shareholders');
      setShareholders([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const totalApprovedShares = shareholders.reduce(
    (sum, s) => sum + (parseInt(s.shares_assigned) || 0),
    0
  );
  const availableShares = totalCompanyShares - totalApprovedShares;

  const handleEdit = (shareholderId, currentShares) => {
    console.log('[SHARES LIST] Editing shareholder:', shareholderId, 'current shares:', currentShares);
    setEditingId(shareholderId);
    setEditedShares({ [shareholderId]: currentShares });
  };

  const handleCancelEdit = () => {
    console.log('[SHARES LIST] Canceling edit');
    setEditingId(null);
    setEditedShares({});
  };

  const handleSave = async (shareholder) => {
    const newShares = editedShares[shareholder.id];
    console.log('[SHARES LIST] Saving shares update:', shareholder.id, 'new shares:', newShares);

    if (!newShares || newShares < 1) {
      toast({
        title: 'Invalid Input',
        description: 'Number of shares must be at least 1.',
        variant: 'destructive'
      });
      return;
    }

    try {
      const newTotal = newShares * sharePrice;
      console.log('[SHARES LIST] Calculated new total:', newTotal);

      const result = await updateShareholder(shareholder.id, {
        shares_assigned: newShares,
        investment_amount: newTotal
      });

      if (!result.success) {
        throw result.error;
      }

      console.log('[SHARES LIST] Shares updated successfully');

      toast({
        title: 'Shares Updated',
        description: `Successfully updated shares for ${shareholder.full_name || 'shareholder'}.`,
        className: 'bg-green-600 text-white'
      });

      setEditingId(null);
      setEditedShares({});
      await fetchData();
    } catch (err) {
      console.error('[SHARES LIST] Error updating shares:', err);
      toast({
        title: 'Update Failed',
        description: err.message || 'Failed to update shares.',
        variant: 'destructive'
      });
    }
  };

  const handleDelete = async (shareholderId) => {
    console.log('[SHARES LIST] Deleting shareholder:', shareholderId);
    
    try {
      const result = await deleteShareholder(shareholderId);

      if (!result.success) {
        throw result.error;
      }

      console.log('[SHARES LIST] Shareholder deleted successfully');

      toast({
        title: 'Shareholder Deleted',
        description: 'Shareholder has been removed successfully.',
        className: 'bg-green-600 text-white'
      });

      setDeleteConfirmId(null);
      await fetchData();
    } catch (err) {
      console.error('[SHARES LIST] Error deleting shareholder:', err);
      toast({
        title: 'Delete Failed',
        description: err.message || 'Failed to delete shareholder.',
        variant: 'destructive'
      });
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <Loader2 className="w-12 h-12 animate-spin text-[#003D82] mx-auto mb-4" />
          <p className="text-gray-600">Loading shareholders...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <Alert variant="destructive" className="max-w-2xl mx-auto mt-8">
        <AlertCircle className="h-4 w-4" />
        <AlertDescription>{error}</AlertDescription>
      </Alert>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-800">Shareholders Management</h1>
        <p className="text-gray-600 mt-1">Manage approved shareholders and share allocation</p>
      </div>

      {/* Summary Cards */}
      <div className="grid md:grid-cols-3 gap-6">
        <Card className="border-l-4 border-[#003D82]">
          <CardContent className="p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-sm font-medium text-gray-500 uppercase tracking-wider">
                  Total Company Shares
                </p>
                <h3 className="text-3xl font-bold mt-2 text-gray-800">{totalCompanyShares}</h3>
              </div>
              <div className="p-3 rounded-full bg-[#003D82] bg-opacity-10">
                <Briefcase className="w-6 h-6 text-[#003D82]" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-[#D4AF37]">
          <CardContent className="p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-sm font-medium text-gray-500 uppercase tracking-wider">
                  Total Approved Shares
                </p>
                <h3 className="text-3xl font-bold mt-2 text-gray-800">{totalApprovedShares}</h3>
                <p className="text-xs text-gray-500 mt-1">
                  {shareholders.length} shareholder{shareholders.length !== 1 ? 's' : ''}
                </p>
              </div>
              <div className="p-3 rounded-full bg-[#D4AF37] bg-opacity-10">
                <PieChart className="w-6 h-6 text-[#D4AF37]" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-green-600">
          <CardContent className="p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-sm font-medium text-gray-500 uppercase tracking-wider">
                  Available Shares
                </p>
                <h3 className="text-3xl font-bold mt-2 text-green-700">{availableShares}</h3>
                <p className="text-xs text-gray-500 mt-1">
                  {((availableShares / totalCompanyShares) * 100).toFixed(1)}% remaining
                </p>
              </div>
              <div className="p-3 rounded-full bg-green-600 bg-opacity-10">
                <CheckCircle className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Shareholders Table */}
      <Card>
        <CardHeader>
          <CardTitle>Approved Shareholders</CardTitle>
        </CardHeader>
        <CardContent>
          {shareholders.length === 0 ? (
            <div className="text-center py-12">
              <Briefcase className="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-xl font-bold text-gray-800 mb-2">No Approved Shareholders</h3>
              <p className="text-gray-600">No shareholders have been approved yet.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Full Name</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead>Country</TableHead>
                    <TableHead>Approved Shares</TableHead>
                    <TableHead>Total Investment</TableHead>
                    <TableHead>Payment Status</TableHead>
                    <TableHead>Approved Date</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {shareholders.map((shareholder) => {
                    const isEditing = editingId === shareholder.id;
                    const displayShares = isEditing
                      ? editedShares[shareholder.id]
                      : shareholder.shares_assigned;

                    return (
                      <TableRow key={shareholder.id}>
                        <TableCell className="font-medium">
                          {shareholder.full_name || 'N/A'}
                        </TableCell>
                        <TableCell>{shareholder.email || 'N/A'}</TableCell>
                        <TableCell>{shareholder.full_phone_number || 'N/A'}</TableCell>
                        <TableCell>
                          {formatCountryCodeDisplay(shareholder.country_code)}
                        </TableCell>
                        <TableCell>
                          {isEditing ? (
                            <Input
                              type="number"
                              min="1"
                              value={editedShares[shareholder.id] || ''}
                              onChange={(e) =>
                                setEditedShares({
                                  ...editedShares,
                                  [shareholder.id]: parseInt(e.target.value) || 0
                                })
                              }
                              className="w-24"
                            />
                          ) : (
                            <span className="font-bold text-blue-600">{displayShares}</span>
                          )}
                        </TableCell>
                        <TableCell className="font-semibold">
                          {formatPrice(
                            isEditing
                              ? (editedShares[shareholder.id] || 0) * sharePrice
                              : shareholder.investment_amount || 0,
                            currency
                          )}
                        </TableCell>
                        <TableCell>
                          <Badge
                            variant="outline"
                            className={
                              shareholder.payment_status === 'completed' ||
                              shareholder.payment_status === 'paid'
                                ? 'bg-green-50 text-green-700 border-green-300'
                                : 'bg-yellow-50 text-yellow-700 border-yellow-300'
                            }
                          >
                            {shareholder.payment_status || 'pending'}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          {shareholder.approved_at
                            ? format(new Date(shareholder.approved_at), 'MMM dd, yyyy')
                            : 'N/A'}
                        </TableCell>
                        <TableCell>
                          <div className="flex gap-2">
                            {isEditing ? (
                              <>
                                <Button
                                  size="sm"
                                  onClick={() => handleSave(shareholder)}
                                  className="bg-green-600 hover:bg-green-700"
                                >
                                  <Save className="w-4 h-4" />
                                </Button>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={handleCancelEdit}
                                >
                                  <X className="w-4 h-4" />
                                </Button>
                              </>
                            ) : (
                              <>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() =>
                                    handleEdit(shareholder.id, shareholder.shares_assigned)
                                  }
                                >
                                  <Edit className="w-4 h-4" />
                                </Button>
                                <Button
                                  size="sm"
                                  variant="destructive"
                                  onClick={() => setDeleteConfirmId(shareholder.id)}
                                >
                                  <Trash2 className="w-4 h-4" />
                                </Button>
                              </>
                            )}
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmId} onOpenChange={() => setDeleteConfirmId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete this shareholder. This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteConfirmId && handleDelete(deleteConfirmId)}
              className="bg-red-600 hover:bg-red-700"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default SharesListPage;