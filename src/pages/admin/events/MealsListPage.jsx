import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_NAV } from '@/config/eventNavConfig';
import { getAllMeals, deleteMeal } from '@/services/mealsService';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Plus, Trash2, Pencil } from 'lucide-react';
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
  AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { updateMeal } from '@/services/mealsService';

const MealsListPage = () => {
  const [meals, setMeals] = useState([]);
  const [loading, setLoading] = useState(true);
  const [deleteId, setDeleteId] = useState(null);
  const [editItem, setEditItem] = useState(null);
  const [form, setForm] = useState({ name: '', description: '', category: 'General' });
  const { toast } = useToast();

  const load = async () => {
    setLoading(true);
    try {
      setMeals(await getAllMeals());
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
    setLoading(false);
  };

  useEffect(() => { load(); }, []);

  const handleSaveEdit = async () => {
    if (!editItem?.id || !form.name.trim()) return;
    try {
      await updateMeal(editItem.id, form);
      toast({ title: 'Updated', description: 'Meal saved.' });
      setEditItem(null);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const handleDelete = async () => {
    if (!deleteId) return;
    try {
      await deleteMeal(deleteId);
      toast({ title: 'Deleted', description: 'Meal removed.' });
      setDeleteId(null);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div>
      <AdminHorizontalNav items={EVENT_NAV} title="Meals" description="Manage meal options for events." />
      <div className="flex justify-end mb-4">
        <Button asChild className="bg-[#D4AF37] text-[#003D82]">
          <Link to="/admin/events/meals/create"><Plus className="w-4 h-4 mr-2" /> Create Meal</Link>
        </Button>
      </div>
      <div className="bg-white rounded-lg border shadow-sm overflow-hidden">
        {loading ? (
          <div className="flex justify-center py-12"><Loader2 className="animate-spin text-gray-400" /></div>
        ) : meals.length === 0 ? (
          <p className="text-center py-12 text-gray-500">No meals yet. Create your first meal option.</p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Description</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {meals.map((m) => (
                <TableRow key={m.id}>
                  <TableCell className="font-medium">{m.name}</TableCell>
                  <TableCell>{m.category || 'General'}</TableCell>
                  <TableCell className="text-gray-600 max-w-md truncate">{m.description}</TableCell>
                  <TableCell className="text-right space-x-1">
                    <Button variant="ghost" size="icon" onClick={() => { setEditItem(m); setForm({ name: m.name, description: m.description || '', category: m.category || 'General' }); }}>
                      <Pencil className="w-4 h-4" />
                    </Button>
                    <Button variant="ghost" size="icon" className="text-red-500" onClick={() => setDeleteId(m.id)}>
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </div>

      <Dialog open={Boolean(editItem)} onOpenChange={(o) => !o && setEditItem(null)}>
        <DialogContent>
          <DialogHeader><DialogTitle>Edit Meal</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div><Label>Name</Label><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></div>
            <div><Label>Category</Label><Input value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} /></div>
            <div><Label>Description</Label><Textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditItem(null)}>Cancel</Button>
            <Button onClick={handleSaveEdit}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={Boolean(deleteId)} onOpenChange={(o) => !o && setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete meal?</AlertDialogTitle>
            <AlertDialogDescription>This meal will no longer be available for new events.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-red-600">Delete</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default MealsListPage;
