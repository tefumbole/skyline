import React, { useState, useEffect } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { getAllCategories } from '@/services/timeSheetCategoriesService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Trash2, Edit2, Plus, Briefcase, Loader2, Tag } from 'lucide-react';

const ActivityManagementPage = () => {
  const { activities, addActivity, deleteActivity, updateActivity } = useTimeSheet();
  const [formData, setFormData] = useState({ name: '', description: '', category: '' });
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [editingActivity, setEditingActivity] = useState(null);
  const [filterCategory, setFilterCategory] = useState('all');

  useEffect(() => {
    loadCategories();
  }, []);

  const loadCategories = async () => {
    const data = await getAllCategories();
    setCategories(data);
    if(data.length > 0) {
        setFormData(prev => ({...prev, category: data[0].name}));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    await addActivity(formData);
    setLoading(false);
    setFormData({ name: '', description: '', category: categories[0]?.name || '' });
  };

  const handleEditClick = (activity) => {
      setEditingActivity(activity);
      setEditModalOpen(true);
  };

  const handleUpdate = async (e) => {
      e.preventDefault();
      if (!editingActivity) return;
      await updateActivity(editingActivity.id, editingActivity);
      setEditModalOpen(false);
      setEditingActivity(null);
  };

  const handleDelete = (id) => {
      if (confirm("Are you sure you want to delete this activity?")) {
          deleteActivity(id);
      }
  };

  const filteredActivities = filterCategory === 'all' 
    ? activities 
    : activities.filter(a => a.category === filterCategory);

  return (
    <div className="max-w-6xl mx-auto p-4 md:p-8 space-y-8 animate-in fade-in duration-500">
        <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Activity Management</h1>
            <p className="text-gray-500">Create and manage your task categories for time tracking.</p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
            {/* Create Form */}
            <Card className="md:col-span-1 h-fit border-t-4 border-t-[#003D82] shadow-md">
                <CardHeader>
                    <CardTitle className="text-lg">Create New Activity</CardTitle>
                    <CardDescription>Define a new task type.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Activity Name <span className="text-red-500">*</span></Label>
                            <Input 
                                id="name" 
                                placeholder="e.g. Frontend Development" 
                                value={formData.name}
                                onChange={(e) => setFormData({...formData, name: e.target.value})}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="category">Category</Label>
                            <Select 
                                value={formData.category} 
                                onValueChange={(val) => setFormData({...formData, category: val})}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map(c => (
                                        <SelectItem key={c.id} value={c.name}>
                                            <div className="flex items-center gap-2">
                                                <div className="w-2 h-2 rounded-full" style={{background: c.color}}></div>
                                                {c.name}
                                            </div>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {categories.length === 0 && <p className="text-xs text-amber-600">No categories found. Admin needs to create them.</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea 
                                id="description" 
                                placeholder="Optional details..." 
                                value={formData.description}
                                onChange={(e) => setFormData({...formData, description: e.target.value})}
                            />
                        </div>
                        <Button type="submit" className="w-full bg-[#003D82]" disabled={loading || categories.length === 0}>
                            {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Plus className="w-4 h-4 mr-2" />}
                            Create Activity
                        </Button>
                    </form>
                </CardContent>
            </Card>

            {/* Activities List */}
            <Card className="md:col-span-2 shadow-md">
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Your Activities</CardTitle>
                        <CardDescription>Manage your existing activity definitions.</CardDescription>
                    </div>
                    <div className="w-48">
                         <Select value={filterCategory} onValueChange={setFilterCategory}>
                             <SelectTrigger className="h-8">
                                 <SelectValue placeholder="Filter Category" />
                             </SelectTrigger>
                             <SelectContent>
                                 <SelectItem value="all">All Categories</SelectItem>
                                 {categories.map(c => <SelectItem key={c.id} value={c.name}>{c.name}</SelectItem>)}
                             </SelectContent>
                         </Select>
                    </div>
                </CardHeader>
                <CardContent>
                    {activities.length === 0 ? (
                        <div className="text-center p-8 border-2 border-dashed rounded-lg bg-gray-50">
                            <Briefcase className="w-10 h-10 text-gray-300 mx-auto mb-3" />
                            <p className="text-gray-500">No activities found. Create one to get started.</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {filteredActivities.length === 0 && <p className="text-center py-4 text-gray-500">No activities match filter.</p>}
                            {filteredActivities.map((activity) => {
                                const catData = categories.find(c => c.name === activity.category);
                                const color = catData?.color || '#ccc';

                                return (
                                    <div key={activity.id} className="flex items-start justify-between p-4 bg-white border rounded-lg hover:shadow-sm transition-shadow">
                                        <div className="flex gap-4">
                                            <div className="p-2 rounded h-fit mt-1 text-white" style={{ backgroundColor: color }}>
                                                <Briefcase className="w-5 h-5" />
                                            </div>
                                            <div>
                                                <h3 className="font-bold text-gray-800">{activity.activity_name || activity.name}</h3>
                                                <div className="flex items-center gap-2 mt-1">
                                                    <span className="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border flex items-center gap-1">
                                                        <Tag className="w-3 h-3" />
                                                        {activity.category}
                                                    </span>
                                                </div>
                                                {activity.description && <p className="text-sm text-gray-500 mt-2">{activity.description}</p>}
                                            </div>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button variant="ghost" size="sm" onClick={() => handleEditClick(activity)}>
                                                <Edit2 className="w-4 h-4 text-blue-600" />
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => handleDelete(activity.id)}>
                                                <Trash2 className="w-4 h-4 text-red-500" />
                                            </Button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>

        {/* Edit Modal */}
        <Dialog open={editModalOpen} onOpenChange={setEditModalOpen}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Activity</DialogTitle>
                </DialogHeader>
                {editingActivity && (
                    <form onSubmit={handleUpdate} className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label>Activity Name</Label>
                            <Input 
                                value={editingActivity.activity_name || editingActivity.name} 
                                onChange={(e) => setEditingActivity({...editingActivity, name: e.target.value, activity_name: e.target.value})}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Category</Label>
                            <Select 
                                value={editingActivity.category} 
                                onValueChange={(val) => setEditingActivity({...editingActivity, category: val})}
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {categories.map(c => (
                                        <SelectItem key={c.id} value={c.name}>{c.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Description</Label>
                            <Textarea 
                                value={editingActivity.description || ''} 
                                onChange={(e) => setEditingActivity({...editingActivity, description: e.target.value})}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditModalOpen(false)}>Cancel</Button>
                            <Button type="submit" className="bg-[#003D82]">Save Changes</Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    </div>
  );
};

export default ActivityManagementPage;