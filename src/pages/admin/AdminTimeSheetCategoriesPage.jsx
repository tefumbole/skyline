import React, { useState, useEffect } from 'react';
import { getAllCategories, createCategory, updateCategory, deleteCategory } from '@/services/timeSheetCategoriesService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/use-toast';
import { Plus, Edit2, Trash2, Tag, Loader2 } from 'lucide-react';

const AdminTimeSheetCategoriesPage = () => {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const { toast } = useToast();

  const [formData, setFormData] = useState({
    name: '',
    description: '',
    color: '#3b82f6'
  });

  useEffect(() => {
    loadCategories();
  }, []);

  const loadCategories = async () => {
    const data = await getAllCategories();
    setCategories(data);
  };

  const handleOpenModal = (category = null) => {
    if (category) {
      setEditingCategory(category);
      setFormData({
        name: category.name,
        description: category.description || '',
        color: category.color || '#3b82f6'
      });
    } else {
      setEditingCategory(null);
      setFormData({ name: '', description: '', color: '#3b82f6' });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) return;

    setLoading(true);
    try {
      if (editingCategory) {
        await updateCategory(editingCategory.id, formData);
        toast({ title: "Updated", description: "Category updated successfully." });
      } else {
        await createCategory(formData);
        toast({ title: "Created", description: "New category added." });
      }
      setIsModalOpen(false);
      loadCategories();
    } catch (error) {
      toast({ title: "Error", description: error.message, variant: "destructive" });
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (confirm("Are you sure you want to delete this category?")) {
      await deleteCategory(id);
      toast({ title: "Deleted", description: "Category removed." });
      loadCategories();
    }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-[#003D82]">TimeSheet Categories</h1>
          <p className="text-gray-500">Manage task categories available for employee time logging.</p>
        </div>
        <Button onClick={() => handleOpenModal()} className="bg-[#003D82]">
          <Plus className="w-4 h-4 mr-2" /> Add Category
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Category Name</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Color Tag</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {categories.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={4} className="text-center py-8 text-gray-500">No categories found. Create one.</TableCell>
                </TableRow>
              ) : (
                categories.map((cat) => (
                  <TableRow key={cat.id}>
                    <TableCell className="font-medium">{cat.name}</TableCell>
                    <TableCell className="text-gray-500">{cat.description || '-'}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <div className="w-6 h-6 rounded-full border shadow-sm" style={{ backgroundColor: cat.color }}></div>
                        <span className="text-xs text-gray-400 font-mono">{cat.color}</span>
                      </div>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button variant="ghost" size="sm" onClick={() => handleOpenModal(cat)}>
                        <Edit2 className="w-4 h-4 text-gray-500" />
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => handleDelete(cat.id)}>
                        <Trash2 className="w-4 h-4 text-red-500" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editingCategory ? 'Edit Category' : 'New Category'}</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="name">Category Name <span className="text-red-500">*</span></Label>
              <Input 
                id="name" 
                value={formData.name} 
                onChange={e => setFormData({...formData, name: e.target.value})} 
                placeholder="e.g. Development" 
                required 
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="color">Color Tag</Label>
              <div className="flex gap-2">
                 <Input 
                  type="color" 
                  id="color" 
                  value={formData.color} 
                  onChange={e => setFormData({...formData, color: e.target.value})} 
                  className="w-12 h-10 p-1 cursor-pointer"
                />
                <Input 
                   value={formData.color}
                   onChange={e => setFormData({...formData, color: e.target.value})}
                   className="font-mono"
                   placeholder="#000000"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea 
                id="description" 
                value={formData.description} 
                onChange={e => setFormData({...formData, description: e.target.value})} 
                placeholder="Optional details..." 
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)}>Cancel</Button>
              <Button type="submit" className="bg-[#003D82]" disabled={loading}>
                {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Save Category'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default AdminTimeSheetCategoriesPage;