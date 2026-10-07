import React, { useEffect, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { hrService } from '@/services/hrService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/use-toast';

function TypesPage({ title, subtitle, listFn, createFn, updateFn, deleteFn, bulkDeleteFn }) {
  const { toast } = useToast();
  const [rows, setRows] = useState([]);
  const [open, setOpen] = useState(false);
  const [selected, setSelected] = useState(new Set());
  const [form, setForm] = useState({ name: '', default_amount: 0 });

  const load = async () => {
    const res = await listFn();
    setRows(res.data || []);
    setSelected(new Set());
  };

  useEffect(() => { load(); }, []);

  const toggleAll = (checked) => {
    setSelected(checked ? new Set(rows.map((r) => r.id)) : new Set());
  };

  const toggleOne = (id, checked) => {
    const next = new Set(selected);
    if (checked) next.add(id);
    else next.delete(id);
    setSelected(next);
  };

  const save = async () => {
    try {
      await createFn(form);
      toast({ title: 'Created' });
      setOpen(false);
      setForm({ name: '', default_amount: 0 });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const saveEdit = async (id, default_amount) => {
    try {
      await updateFn(id, { default_amount: Number(default_amount) });
      toast({ title: 'Updated' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const removeOne = async (id, name) => {
    if (!confirm(`Delete "${name}"?`)) return;
    try {
      await deleteFn(id);
      toast({ title: 'Deleted' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const removeSelected = async () => {
    if (!selected.size) return;
    if (!confirm(`Delete ${selected.size} selected item(s)?`)) return;
    try {
      await bulkDeleteFn([...selected]);
      toast({ title: 'Deleted', description: `${selected.size} item(s) removed.` });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap justify-between items-center gap-3">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">{title}</h1>
          <p className="text-gray-500">{subtitle}</p>
        </div>
        <div className="flex gap-2">
          {selected.size > 0 && (
            <Button variant="destructive" onClick={removeSelected}>
              <Trash2 className="w-4 h-4 mr-1" /> Delete selected ({selected.size})
            </Button>
          )}
          <Button onClick={() => setOpen(true)} className="bg-[#003D82]"><Plus className="w-4 h-4 mr-2" /> Add type</Button>
        </div>
      </div>
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-10">
                  <Checkbox checked={rows.length > 0 && selected.size === rows.length} onCheckedChange={toggleAll} />
                </TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Default amount</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((r) => (
                <TableRow key={r.id}>
                  <TableCell>
                    <Checkbox checked={selected.has(r.id)} onCheckedChange={(c) => toggleOne(r.id, c)} />
                  </TableCell>
                  <TableCell>{r.name}</TableCell>
                  <TableCell>
                    <Input type="number" className="w-32 h-8" defaultValue={r.default_amount} id={`amt-${r.id}`} />
                  </TableCell>
                  <TableCell className="text-right space-x-1">
                    <Button size="sm" variant="outline" onClick={() => saveEdit(r.id, document.getElementById(`amt-${r.id}`)?.value)}>Save</Button>
                    <Button size="sm" variant="ghost" className="text-red-500" onClick={() => removeOne(r.id, r.name)}><Trash2 className="w-4 h-4" /></Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>Add {title.slice(0, -1)}</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div><Label>Name</Label><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></div>
            <div><Label>Default amount (FCFA)</Label><Input type="number" value={form.default_amount} onChange={(e) => setForm({ ...form, default_amount: e.target.value })} /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button className="bg-[#003D82]" onClick={save}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

export function HrAllowancesPage() {
  return (
    <TypesPage
      title="Allowances"
      subtitle="Food, transport, overtime, accommodation, and other allowance types."
      listFn={hrService.listAllowanceTypes}
      createFn={hrService.createAllowanceType}
      updateFn={hrService.updateAllowanceType}
      deleteFn={hrService.deleteAllowanceType}
      bulkDeleteFn={hrService.bulkDeleteAllowanceTypes}
    />
  );
}

export function HrDeductionsPage() {
  return (
    <TypesPage
      title="Deductions"
      subtitle="Absence, late coming, loans, damages, and other deduction types."
      listFn={hrService.listDeductionTypes}
      createFn={hrService.createDeductionType}
      updateFn={hrService.updateDeductionType}
      deleteFn={hrService.deleteDeductionType}
      bulkDeleteFn={hrService.bulkDeleteDeductionTypes}
    />
  );
}

export default HrAllowancesPage;
