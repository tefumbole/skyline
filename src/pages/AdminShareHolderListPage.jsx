import React, { useState, useEffect } from 'react';
import {
  getAllShareholders,
  softDeleteShareholders,
  updateShareholderName,
  getShareholderDisplayName,
  getShareholderWorth,
} from '@/services/shareholderService';
import { getSystemSettings, formatPrice } from '@/services/sharePriceService';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Search, Trash2, RefreshCw, Pencil } from 'lucide-react';

const AdminShareHolderListPage = () => {
  const [shareholders, setShareholders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedIds, setSelectedIds] = useState([]);
  const [pricePerShare, setPricePerShare] = useState(1000);
  const [currency, setCurrency] = useState('USD');
  const [editItem, setEditItem] = useState(null);
  const [editName, setEditName] = useState('');
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false);
  const { toast } = useToast();

  const fetchShareholders = async () => {
    setLoading(true);
    try {
      const [data, settings] = await Promise.all([
        getAllShareholders({ status: 'approved' }),
        getSystemSettings(),
      ]);
      setShareholders(data.filter((s) => s.status === 'approved'));
      setPricePerShare(settings.price_per_share || 1000);
      setCurrency(settings.currency || 'USD');
      setSelectedIds([]);
    } catch (error) {
      toast({ title: 'Error fetching data', description: error.message, variant: 'destructive' });
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchShareholders();
  }, []);

  const filteredShareholders = shareholders.filter((s) => {
    const term = searchTerm.toLowerCase();
    return (
      getShareholderDisplayName(s).toLowerCase().includes(term) ||
      s.email?.toLowerCase().includes(term) ||
      s.reference_number?.toLowerCase().includes(term)
    );
  });

  const totalSharesListed = filteredShareholders.reduce(
    (sum, s) => sum + (parseInt(s.shares_assigned, 10) || 0),
    0
  );

  const toggleAll = (checked) => {
    setSelectedIds(checked ? filteredShareholders.map((s) => s.id) : []);
  };

  const toggleOne = (id, checked) => {
    setSelectedIds((prev) => (checked ? [...prev, id] : prev.filter((x) => x !== id)));
  };

  const handleSaveName = async () => {
    if (!editItem || !editName.trim()) return;
    const result = await updateShareholderName(editItem.id, editName);
    if (!result.success) {
      toast({ title: 'Error', description: 'Failed to update name', variant: 'destructive' });
      return;
    }
    toast({ title: 'Updated', description: 'Shareholder name saved.' });
    setEditItem(null);
    fetchShareholders();
  };

  const handleDeleteSelected = async () => {
    const result = await softDeleteShareholders(selectedIds);
    setConfirmDeleteOpen(false);
    if (!result.success) {
      toast({ title: 'Error', description: 'Failed to move records to trash', variant: 'destructive' });
      return;
    }
    toast({
      title: 'Moved to Trash',
      description: `${selectedIds.length} record(s) moved to trash.`,
    });
    fetchShareholders();
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-col md:flex-row justify-between gap-4 items-start md:items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Shareholder Management</h1>
          <p className="text-sm text-gray-500 mt-1">
            Listed shares total: <strong>{totalSharesListed}</strong>
          </p>
        </div>
        <div className="flex gap-2">
          {selectedIds.length > 0 && (
            <Button variant="destructive" onClick={() => setConfirmDeleteOpen(true)}>
              <Trash2 className="h-4 w-4 mr-2" /> Delete ({selectedIds.length})
            </Button>
          )}
          <Button onClick={fetchShareholders} variant="outline" className="gap-2">
            <RefreshCw className="h-4 w-4" /> Refresh
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <div className="flex flex-col md:flex-row justify-between gap-4">
            <CardTitle>Registered Shareholders</CardTitle>
            <div className="relative w-full md:w-72">
              <Search className="absolute left-2 top-2.5 h-4 w-4 text-gray-500" />
              <Input
                placeholder="Search name, email, ref..."
                className="pl-8"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
            </div>
          ) : filteredShareholders.length === 0 ? (
            <div className="text-center py-8 text-gray-500">No shareholders found.</div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-10">
                      <Checkbox
                        checked={selectedIds.length === filteredShareholders.length && filteredShareholders.length > 0}
                        onCheckedChange={toggleAll}
                      />
                    </TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Reference</TableHead>
                    <TableHead>Contact</TableHead>
                    <TableHead>Shares</TableHead>
                    <TableHead>Worth</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredShareholders.map((s) => (
                    <TableRow key={s.id}>
                      <TableCell>
                        <Checkbox
                          checked={selectedIds.includes(s.id)}
                          onCheckedChange={(checked) => toggleOne(s.id, checked)}
                        />
                      </TableCell>
                      <TableCell className="font-medium">{getShareholderDisplayName(s)}</TableCell>
                      <TableCell className="font-mono text-xs">{s.reference_number || '—'}</TableCell>
                      <TableCell>
                        <div className="flex flex-col text-sm">
                          <span>{s.email || '—'}</span>
                          <span className="text-gray-500 text-xs">{s.full_phone_number || s.phone_number || s.phone || '—'}</span>
                        </div>
                      </TableCell>
                      <TableCell>{s.shares_assigned || 0}</TableCell>
                      <TableCell>{formatPrice(getShareholderWorth(s, pricePerShare), currency)}</TableCell>
                      <TableCell>
                        <Badge className={s.status === 'approved' ? 'bg-green-600' : 'bg-yellow-500'}>
                          {s.status || 'pending'}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right space-x-1">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => {
                            setEditItem(s);
                            setEditName(getShareholderDisplayName(s) === '—' ? '' : getShareholderDisplayName(s));
                          }}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-red-500 hover:text-red-700 hover:bg-red-50"
                          onClick={() => {
                            setSelectedIds([s.id]);
                            setConfirmDeleteOpen(true);
                          }}
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

      <Dialog open={Boolean(editItem)} onOpenChange={(open) => !open && setEditItem(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Update Shareholder Name</DialogTitle>
          </DialogHeader>
          <Input value={editName} onChange={(e) => setEditName(e.target.value)} placeholder="Full name" />
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditItem(null)}>Cancel</Button>
            <Button onClick={handleSaveName}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={confirmDeleteOpen} onOpenChange={setConfirmDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Move to Trash?</AlertDialogTitle>
            <AlertDialogDescription>
              {selectedIds.length} shareholder record(s) will be moved to trash. You can restore them later from the Trash menu.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDeleteSelected} className="bg-red-600 hover:bg-red-700">
              Confirm Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default AdminShareHolderListPage;
