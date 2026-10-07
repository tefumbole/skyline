import React, { useEffect, useState } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Plus, Trash2 } from 'lucide-react';

const slugify = (value) =>
  String(value || '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');

const AnnouncementCategoriesPage = () => {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const { toast } = useToast();

  const load = async () => {
    setLoading(true);
    const { data, error } = await supabase
      .from('announcement_categories')
      .select('*')
      .eq('is_active', true)
      .order('name');
    if (error) toast({ title: 'Error', description: error.message, variant: 'destructive' });
    setCategories(data || []);
    setLoading(false);
  };

  useEffect(() => { load(); }, []);

  const handleCreate = async (e) => {
    e.preventDefault();
    if (!name.trim()) return;
    const slug = slugify(name);
    const { error } = await supabase.from('announcement_categories').insert([{
      name: name.trim(),
      slug,
      description: description.trim() || null,
      is_active: true,
    }]);
    if (error) {
      toast({ title: 'Error', description: error.message, variant: 'destructive' });
      return;
    }
    toast({ title: 'Category created' });
    setName('');
    setDescription('');
    load();
  };

  const handleDelete = async (id) => {
    const { error } = await supabase.from('announcement_categories').update({ is_active: false }).eq('id', id);
    if (error) {
      toast({ title: 'Error', description: error.message, variant: 'destructive' });
      return;
    }
    load();
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Announcement Categories</h1>
        <p className="text-gray-500">Organize announcements by category for filtering and templates.</p>
      </div>

      <form onSubmit={handleCreate} className="bg-white border rounded-lg p-4 grid md:grid-cols-3 gap-3 items-end">
        <div><Label>Name</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Finance Updates" /></div>
        <div><Label>Description</Label><Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Optional" /></div>
        <Button type="submit" className="bg-[#003D82]"><Plus className="w-4 h-4 mr-2" /> Add Category</Button>
      </form>

      <div className="bg-white border rounded-lg overflow-hidden">
        {loading ? (
          <div className="flex justify-center py-10"><Loader2 className="animate-spin text-gray-400" /></div>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Slug</TableHead>
                <TableHead>Description</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {categories.map((c) => (
                <TableRow key={c.id}>
                  <TableCell className="font-medium">{c.name}</TableCell>
                  <TableCell className="font-mono text-xs">{c.slug}</TableCell>
                  <TableCell>{c.description || '—'}</TableCell>
                  <TableCell className="text-right">
                    <Button variant="ghost" size="icon" className="text-red-500" onClick={() => handleDelete(c.id)}>
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </div>
    </div>
  );
};

export default AnnouncementCategoriesPage;
