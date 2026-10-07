import React, { useState, useEffect } from 'react';
import {
  getAllShareholders,
  restoreShareholders,
  permanentDeleteShareholders,
  getShareholderDisplayName,
  getShareholderWorth,
} from '@/services/shareholderService';
import { getSystemSettings, formatPrice } from '@/services/sharePriceService';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
import { Loader2, Trash2, RefreshCw, RotateCcw } from 'lucide-react';
import { format } from 'date-fns';

const AdminShareHolderTrashPage = () => {
  const [shareholders, setShareholders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedIds, setSelectedIds] = useState([]);
  const [pricePerShare, setPricePerShare] = useState(1000);
  const [currency, setCurrency] = useState('USD');
  const [confirmAction, setConfirmAction] = useState(null);
  const { toast } = useToast();

  const fetchTrash = async () => {
    setLoading(true);
    try {
      const [data, settings] = await Promise.all([
        getAllShareholders({ trashOnly: true }),
        getSystemSettings(),
      ]);
      setShareholders(data);
      setPricePerShare(settings.price_per_share || 1000);
      setCurrency(settings.currency || 'USD');
      setSelectedIds([]);
    } catch (error) {
      toast({ title: 'Error', description: error.message, variant: 'destructive' });
    }
    setLoading(false);
  };

  useEffect(() => {
    fetchTrash();
  }, []);

  const runAction = async () => {
    if (!confirmAction) return;
    const { type, ids } = confirmAction;
    const result =
      type === 'restore'
        ? await restoreShareholders(ids)
        : await permanentDeleteShareholders(ids);

    setConfirmAction(null);
    if (!result.success) {
      toast({ title: 'Error', description: 'Action failed', variant: 'destructive' });
      return;
    }
    toast({
      title: type === 'restore' ? 'Restored' : 'Permanently deleted',
      description: `${ids.length} record(s) updated.`,
    });
    fetchTrash();
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Shareholder Trash</h1>
          <p className="text-sm text-gray-500">Deleted records can be restored or permanently removed.</p>
        </div>
        <Button onClick={fetchTrash} variant="outline" className="gap-2">
          <RefreshCw className="h-4 w-4" /> Refresh
        </Button>
      </div>

      {selectedIds.length > 0 && (
        <div className="flex gap-2">
          <Button onClick={() => setConfirmAction({ type: 'restore', ids: selectedIds })}>
            <RotateCcw className="h-4 w-4 mr-2" /> Restore ({selectedIds.length})
          </Button>
          <Button
            variant="destructive"
            onClick={() => setConfirmAction({ type: 'purge', ids: selectedIds })}
          >
            <Trash2 className="h-4 w-4 mr-2" /> Delete Forever ({selectedIds.length})
          </Button>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Trashed Shareholders</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
            </div>
          ) : shareholders.length === 0 ? (
            <div className="text-center py-8 text-gray-500">Trash is empty.</div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10">
                    <Checkbox
                      checked={selectedIds.length === shareholders.length}
                      onCheckedChange={(checked) =>
                        setSelectedIds(checked ? shareholders.map((s) => s.id) : [])
                      }
                    />
                  </TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Shares</TableHead>
                  <TableHead>Worth</TableHead>
                  <TableHead>Deleted</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {shareholders.map((s) => (
                  <TableRow key={s.id}>
                    <TableCell>
                      <Checkbox
                        checked={selectedIds.includes(s.id)}
                        onCheckedChange={(checked) =>
                          setSelectedIds((prev) =>
                            checked ? [...prev, s.id] : prev.filter((x) => x !== s.id)
                          )
                        }
                      />
                    </TableCell>
                    <TableCell>{getShareholderDisplayName(s)}</TableCell>
                    <TableCell>{s.shares_assigned || 0}</TableCell>
                    <TableCell>{formatPrice(getShareholderWorth(s, pricePerShare), currency)}</TableCell>
                    <TableCell>
                      {s.deleted_at ? format(new Date(s.deleted_at), 'MMM dd, yyyy HH:mm') : '—'}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <AlertDialog open={Boolean(confirmAction)} onOpenChange={(open) => !open && setConfirmAction(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {confirmAction?.type === 'restore' ? 'Restore records?' : 'Delete permanently?'}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {confirmAction?.type === 'restore'
                ? `${confirmAction?.ids?.length || 0} record(s) will be restored to the shareholder list.`
                : `${confirmAction?.ids?.length || 0} record(s) will be permanently deleted. This cannot be undone.`}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={runAction}
              className={confirmAction?.type === 'purge' ? 'bg-red-600 hover:bg-red-700' : ''}
            >
              Confirm
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default AdminShareHolderTrashPage;
