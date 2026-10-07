import React, { useEffect, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { hrService, formatFcfa } from '@/services/hrService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';

export default function HrStaffCategoriesPage() {
  const { toast } = useToast();
  const [categories, setCategories] = useState([]);
  const [rates, setRates] = useState([]);
  const [catOpen, setCatOpen] = useState(false);
  const [rateOpen, setRateOpen] = useState(false);
  const [catForm, setCatForm] = useState({ name: '', code: '', description: '' });
  const [rateForm, setRateForm] = useState({ position: '', daily_rate: '' });

  const load = async () => {
    const [c, r] = await Promise.all([hrService.listCategories(), hrService.listPositionRates()]);
    setCategories(c.data || []);
    setRates(r.data || []);
  };

  useEffect(() => { load(); }, []);

  const saveRate = async (id, daily_rate) => {
    try {
      await hrService.updatePositionRate(id, { daily_rate: Number(daily_rate) });
      toast({ title: 'Saved', description: 'Daily rate updated.' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const addCategory = async () => {
    try {
      await hrService.createCategory(catForm);
      toast({ title: 'Created', description: 'Category added.' });
      setCatOpen(false);
      setCatForm({ name: '', code: '', description: '' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const deleteCategory = async (id, name) => {
    if (!confirm(`Delete category "${name}"?`)) return;
    try {
      await hrService.deleteCategory(id);
      toast({ title: 'Deleted' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const addRate = async () => {
    try {
      await hrService.createPositionRate({ position: rateForm.position, daily_rate: Number(rateForm.daily_rate) });
      toast({ title: 'Created', description: 'Position rate added.' });
      setRateOpen(false);
      setRateForm({ position: '', daily_rate: '' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const deleteRate = async (id, position) => {
    if (!confirm(`Delete rate for "${position}"?`)) return;
    try {
      await hrService.deletePositionRate(id);
      toast({ title: 'Deleted' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Staff Categories</h1>
        <p className="text-gray-500">Manage staff groupings and default daily rates.</p>
      </div>

      <div className="grid md:grid-cols-2 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Categories</CardTitle>
            <Button size="sm" className="bg-[#003D82]" onClick={() => setCatOpen(true)}><Plus className="w-4 h-4 mr-1" /> Add</Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {categories.map((c) => (
              <div key={c.id} className="border rounded-lg p-4 flex justify-between gap-3">
                <div>
                  <div className="font-semibold text-[#003D82]">{c.name}</div>
                  <p className="text-sm text-gray-600 mt-1">{c.description}</p>
                  <div className="text-xs text-gray-400 mt-2 uppercase">{c.code}</div>
                </div>
                <Button size="sm" variant="ghost" className="text-red-500 shrink-0" onClick={() => deleteCategory(c.id, c.name)}>
                  <Trash2 className="w-4 h-4" />
                </Button>
              </div>
            ))}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Default Daily Rates</CardTitle>
            <Button size="sm" className="bg-[#003D82]" onClick={() => setRateOpen(true)}><Plus className="w-4 h-4 mr-1" /> Add</Button>
          </CardHeader>
          <CardContent className="space-y-3">
            {rates.map((r) => (
              <div key={r.id} className="flex items-end gap-2 border rounded-lg p-3">
                <div className="flex-1">
                  <Label className="text-xs">{r.position}</Label>
                  <Input type="number" defaultValue={r.daily_rate} id={`rate-${r.id}`} />
                </div>
                <Button size="sm" className="bg-[#003D82]" onClick={() => saveRate(r.id, document.getElementById(`rate-${r.id}`)?.value)}>Save</Button>
                <Button size="sm" variant="ghost" className="text-red-500" onClick={() => deleteRate(r.id, r.position)}><Trash2 className="w-4 h-4" /></Button>
              </div>
            ))}
            <p className="text-xs text-gray-500">Rates apply when creating staff but can be edited per person.</p>
          </CardContent>
        </Card>
      </div>

      <Dialog open={catOpen} onOpenChange={setCatOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Add category</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div><Label>Name</Label><Input value={catForm.name} onChange={(e) => setCatForm({ ...catForm, name: e.target.value })} /></div>
            <div><Label>Code (optional)</Label><Input value={catForm.code} onChange={(e) => setCatForm({ ...catForm, code: e.target.value })} placeholder="e.g. seasonal" /></div>
            <div><Label>Description</Label><Textarea value={catForm.description} onChange={(e) => setCatForm({ ...catForm, description: e.target.value })} /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCatOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={addCategory}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={rateOpen} onOpenChange={setRateOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Add position rate</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div><Label>Position title</Label><Input value={rateForm.position} onChange={(e) => setRateForm({ ...rateForm, position: e.target.value })} /></div>
            <div><Label>Daily rate (FCFA)</Label><Input type="number" value={rateForm.daily_rate} onChange={(e) => setRateForm({ ...rateForm, daily_rate: e.target.value })} /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setRateOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={addRate}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
