import React, { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { getTaskCategories, createTaskCategory, updateTaskCategory, deleteTaskCategory } from '@/services/taskCategoryService';
import { getMessageTemplates, updateMessageTemplate } from '@/services/taskMessageTemplateService';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Loader2, Plus, Trash2, Save, Tags, Mail } from 'lucide-react';

const TaskSettingsPage = () => {
  const [categories, setCategories] = useState([]);
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);
  
  // Category Form
  const [newCategory, setNewCategory] = useState({ name: '', color: '#3B82F6' });
  const [isSubmittingCat, setIsSubmittingCat] = useState(false);

  // Template Form
  const [editingTemplate, setEditingTemplate] = useState(null);
  const [isSubmittingTpl, setIsSubmittingTpl] = useState(false);

  const { toast } = useToast();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    const [catRes, tplRes] = await Promise.all([getTaskCategories(), getMessageTemplates()]);
    if (catRes.success) setCategories(catRes.data);
    if (tplRes.success) setTemplates(tplRes.data);
    setLoading(false);
  };

  const handleAddCategory = async (e) => {
    e.preventDefault();
    if (!newCategory.name) return;
    setIsSubmittingCat(true);
    const res = await createTaskCategory(newCategory);
    if (res.success) {
      toast({ title: 'Category added' });
      setNewCategory({ name: '', color: '#3B82F6' });
      loadData();
    } else {
      toast({ title: 'Error', description: res.error, variant: 'destructive' });
    }
    setIsSubmittingCat(false);
  };

  const handleDeleteCategory = async (id) => {
    if (!window.confirm('Delete this category? Tasks using it will have category removed.')) return;
    const res = await deleteTaskCategory(id);
    if (res.success) {
      toast({ title: 'Category deleted' });
      loadData();
    } else {
      toast({ title: 'Error', description: res.error, variant: 'destructive' });
    }
  };

  const handleSaveTemplate = async (e) => {
    e.preventDefault();
    if (!editingTemplate) return;
    setIsSubmittingTpl(true);
    const res = await updateMessageTemplate(editingTemplate.id, {
        subject: editingTemplate.subject,
        body: editingTemplate.body,
        is_active: editingTemplate.is_active
    });
    if (res.success) {
      toast({ title: 'Template updated' });
      setEditingTemplate(null);
      loadData();
    } else {
      toast({ title: 'Error', description: res.error, variant: 'destructive' });
    }
    setIsSubmittingTpl(false);
  };

  if (loading) {
    return <div className="flex justify-center py-20"><Loader2 className="w-10 h-10 animate-spin text-[#003D82]" /></div>;
  }

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-[#003D82]">Task Settings</h1>
        <p className="text-gray-500">Manage categories and notification templates.</p>
      </div>

      <Tabs defaultValue="categories" className="w-full">
        <TabsList className="mb-4">
          <TabsTrigger value="categories" className="flex gap-2"><Tags className="w-4 h-4"/> Categories</TabsTrigger>
          <TabsTrigger value="templates" className="flex gap-2"><Mail className="w-4 h-4"/> Message Templates</TabsTrigger>
        </TabsList>

        <TabsContent value="categories">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card className="md:col-span-1">
              <CardHeader>
                <CardTitle>Add Category</CardTitle>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleAddCategory} className="space-y-4">
                  <div className="space-y-2">
                    <Label>Name</Label>
                    <Input value={newCategory.name} onChange={e => setNewCategory({...newCategory, name: e.target.value})} required />
                  </div>
                  <div className="space-y-2">
                    <Label>Color Code</Label>
                    <div className="flex gap-2">
                      <Input type="color" className="w-14 p-1" value={newCategory.color} onChange={e => setNewCategory({...newCategory, color: e.target.value})} />
                      <Input value={newCategory.color} onChange={e => setNewCategory({...newCategory, color: e.target.value})} />
                    </div>
                  </div>
                  <Button type="submit" disabled={isSubmittingCat} className="w-full bg-[#003D82]">
                    {isSubmittingCat ? <Loader2 className="w-4 h-4 animate-spin mr-2"/> : <Plus className="w-4 h-4 mr-2"/>} Add Category
                  </Button>
                </form>
              </CardContent>
            </Card>

            <Card className="md:col-span-2">
              <CardHeader>
                <CardTitle>Existing Categories</CardTitle>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Name</TableHead>
                      <TableHead>Preview</TableHead>
                      <TableHead className="text-right">Action</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {categories.length === 0 ? (
                      <TableRow><TableCell colSpan={3} className="text-center text-gray-500">No categories found</TableCell></TableRow>
                    ) : categories.map(cat => (
                      <TableRow key={cat.id}>
                        <TableCell className="font-medium">{cat.name}</TableCell>
                        <TableCell>
                          <div className="w-24 h-6 rounded border flex items-center justify-center text-xs font-bold" 
                               style={{backgroundColor: `${cat.color}20`, color: cat.color, borderColor: cat.color}}>
                            {cat.name}
                          </div>
                        </TableCell>
                        <TableCell className="text-right">
                          <Button variant="ghost" size="icon" onClick={() => handleDeleteCategory(cat.id)}>
                            <Trash2 className="w-4 h-4 text-red-500" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="templates">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card className="md:col-span-1">
              <CardHeader>
                <CardTitle>Available Templates</CardTitle>
                <CardDescription>Select a template to edit.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-2">
                {templates.map(tpl => (
                  <Button 
                    key={tpl.id} 
                    variant={editingTemplate?.id === tpl.id ? "default" : "outline"}
                    className={`w-full justify-start ${editingTemplate?.id === tpl.id ? 'bg-[#003D82]' : ''}`}
                    onClick={() => setEditingTemplate({...tpl})}
                  >
                    <Mail className="w-4 h-4 mr-2" />
                    {tpl.type.charAt(0).toUpperCase() + tpl.type.slice(1)} Notification
                  </Button>
                ))}

                <div className="mt-8 p-4 bg-gray-50 rounded-md border text-sm">
                  <p className="font-bold mb-2">Available Variables:</p>
                  <ul className="space-y-1 text-gray-600 list-disc list-inside ml-4">
                    <li><code className="bg-gray-200 px-1 rounded">{'{{task_title}}'}</code></li>
                    <li><code className="bg-gray-200 px-1 rounded">{'{{deadline}}'}</code></li>
                    <li><code className="bg-gray-200 px-1 rounded">{'{{priority}}'}</code></li>
                    <li><code className="bg-gray-200 px-1 rounded">{'{{assigned_by}}'}</code></li>
                    <li><code className="bg-gray-200 px-1 rounded">{'{{login_link}}'}</code></li>
                  </ul>
                </div>
              </CardContent>
            </Card>

            <Card className="md:col-span-2">
              <CardHeader>
                <CardTitle>{editingTemplate ? `Edit ${editingTemplate.type} Template` : 'Select a Template'}</CardTitle>
              </CardHeader>
              <CardContent>
                {editingTemplate ? (
                  <form onSubmit={handleSaveTemplate} className="space-y-4">
                    <div className="flex items-center justify-between p-3 border rounded bg-gray-50">
                      <Label htmlFor="active-toggle" className="font-medium cursor-pointer">Enable Notification</Label>
                      <Switch 
                        id="active-toggle" 
                        checked={editingTemplate.is_active} 
                        onCheckedChange={v => setEditingTemplate({...editingTemplate, is_active: v})} 
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Subject</Label>
                      <Input 
                        value={editingTemplate.subject} 
                        onChange={e => setEditingTemplate({...editingTemplate, subject: e.target.value})} 
                        required 
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Message Body</Label>
                      <Textarea 
                        rows={12} 
                        value={editingTemplate.body} 
                        onChange={e => setEditingTemplate({...editingTemplate, body: e.target.value})} 
                        required 
                      />
                    </div>
                    <div className="flex justify-end gap-2">
                      <Button type="button" variant="outline" onClick={() => setEditingTemplate(null)}>Cancel</Button>
                      <Button type="submit" disabled={isSubmittingTpl} className="bg-[#003D82]">
                        {isSubmittingTpl ? <Loader2 className="w-4 h-4 animate-spin mr-2"/> : <Save className="w-4 h-4 mr-2"/>} Save Template
                      </Button>
                    </div>
                  </form>
                ) : (
                  <div className="text-center py-20 text-gray-500">
                    <Mail className="w-12 h-12 mx-auto text-gray-300 mb-3" />
                    Select a template from the list to edit its content.
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default TaskSettingsPage;