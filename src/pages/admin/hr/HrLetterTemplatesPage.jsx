import React, { useEffect, useState } from 'react';
import { Plus, Trash2, Edit2 } from 'lucide-react';
import { hrLetterService, LETTER_TYPES } from '@/services/hrLetterService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';

const TYPE_OPTIONS = Object.entries(LETTER_TYPES).map(([value, meta]) => ({ value, label: meta.label }));

export default function HrLetterTemplatesPage() {
  const { toast } = useToast();
  const [templates, setTemplates] = useState([]);
  const [open, setOpen] = useState(false);
  const [editId, setEditId] = useState(null);
  const [form, setForm] = useState({
    letter_type: 'leave_of_absence', name: '', subject: '', body: '', is_default: false,
  });

  const load = async () => {
    const res = await hrLetterService.listTemplates();
    setTemplates(res.data || []);
  };

  useEffect(() => { load(); }, []);

  const openCreate = () => {
    setEditId(null);
    setForm({ letter_type: 'leave_of_absence', name: '', subject: '', body: '', is_default: false });
    setOpen(true);
  };

  const openEdit = (t) => {
    setEditId(t.id);
    setForm({
      letter_type: t.letter_type,
      name: t.name,
      subject: t.subject,
      body: t.body,
      is_default: Boolean(t.is_default),
    });
    setOpen(true);
  };

  const save = async () => {
    try {
      if (editId) await hrLetterService.updateTemplate(editId, form);
      else await hrLetterService.createTemplate(form);
      toast({ title: editId ? 'Updated' : 'Created' });
      setOpen(false);
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const remove = async (id, name) => {
    if (!confirm(`Delete template "${name}"?`)) return;
    try {
      await hrLetterService.deleteTemplate(id);
      toast({ title: 'Deleted' });
      load();
    } catch (e) {
      toast({ title: 'Error', description: e.message, variant: 'destructive' });
    }
  };

  const typeLabel = (t) => LETTER_TYPES[t]?.label || t;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">Letter Templates</h1>
          <p className="text-gray-500">Manage editable templates for all HR letter types.</p>
        </div>
        <Button className="bg-[#003D82]" onClick={openCreate}><Plus className="w-4 h-4 mr-1" /> New template</Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Subject</TableHead>
                <TableHead>Default</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {templates.map((t) => (
                <TableRow key={t.id}>
                  <TableCell className="font-medium">{t.name}</TableCell>
                  <TableCell>{typeLabel(t.letter_type)}</TableCell>
                  <TableCell className="max-w-xs truncate">{t.subject}</TableCell>
                  <TableCell>{t.is_default ? 'Yes' : '—'}</TableCell>
                  <TableCell className="text-right space-x-1">
                    <Button size="sm" variant="ghost" onClick={() => openEdit(t)}><Edit2 className="w-4 h-4" /></Button>
                    <Button size="sm" variant="ghost" className="text-red-500" onClick={() => remove(t.id, t.name)}><Trash2 className="w-4 h-4" /></Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader><DialogTitle>{editId ? 'Edit template' : 'New template'}</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div>
              <Label>Letter type</Label>
              <Select value={form.letter_type} onValueChange={(v) => setForm({ ...form, letter_type: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {TYPE_OPTIONS.map((o) => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div><Label>Name</Label><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></div>
            <div><Label>Subject</Label><Input value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} /></div>
            <div><Label>Body</Label><Textarea rows={14} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} className="font-mono text-sm" /></div>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={form.is_default} onChange={(e) => setForm({ ...form, is_default: e.target.checked })} />
              Set as default for this letter type
            </label>
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
