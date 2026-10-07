import React, { useState } from 'react';
import { useTimeSheet } from '@/context/TimeSheetContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Calendar, Save, Trash2, Edit2, Loader2, Clock } from 'lucide-react';
import { format } from 'date-fns';

const FillTimeSheetPage = () => {
  const { activities, addEntry, entries, deleteEntry, updateEntry } = useTimeSheet();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
      date: new Date().toISOString().split('T')[0],
      activity_id: '',
      hours: '',
      notes: ''
  });

  // Edit State
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [editingEntry, setEditingEntry] = useState(null);

  const handleSubmit = async (e) => {
      e.preventDefault();
      
      const hours = parseFloat(formData.hours);
      if (hours < 0 || hours > 24) {
          alert("Hours must be between 0 and 24.");
          return;
      }
      if (!formData.activity_id) {
          alert("Please select an activity.");
          return;
      }

      setLoading(true);
      await addEntry({
          ...formData,
          hours: hours,
          activity_name: activities.find(a => a.id === formData.activity_id)?.name || 'Activity' // fallback name
      });
      setLoading(false);
      // Reset some fields
      setFormData(prev => ({ ...prev, activity_id: '', hours: '', notes: '' }));
  };

  const handleEditClick = (entry) => {
      setEditingEntry(entry);
      setEditModalOpen(true);
  };

  const handleUpdate = async () => {
      if(!editingEntry) return;
      await updateEntry(editingEntry.id, editingEntry);
      setEditModalOpen(false);
      setEditingEntry(null);
  };

  const handleDelete = (id) => {
      if(confirm("Are you sure you want to delete this entry?")) {
          deleteEntry(id);
      }
  };

  return (
    <div className="max-w-6xl mx-auto p-4 md:p-8 space-y-8 animate-in fade-in duration-500">
        <div>
            <h1 className="text-3xl font-bold text-[#003D82]">Fill Time Sheet</h1>
            <p className="text-gray-500">Log your daily work hours and activities.</p>
        </div>

        <div className="grid lg:grid-cols-3 gap-8">
            {/* Form Section */}
            <Card className="lg:col-span-1 h-fit shadow-lg border-t-4 border-t-[#003D82]">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Clock className="w-5 h-5 text-[#D4AF37]" /> Log Time
                    </CardTitle>
                    <CardDescription>Record hours for a specific activity.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="date">Date <span className="text-red-500">*</span></Label>
                            <Input 
                                id="date" 
                                type="date" 
                                value={formData.date}
                                onChange={(e) => setFormData({...formData, date: e.target.value})}
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="activity">Activity <span className="text-red-500">*</span></Label>
                            <Select 
                                value={formData.activity_id} 
                                onValueChange={(val) => setFormData({...formData, activity_id: val})}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select activity..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {activities.map(a => (
                                        <SelectItem key={a.id} value={a.id}>{a.activity_name || a.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {activities.length === 0 && <p className="text-xs text-red-500">No activities found. Please create one first.</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="hours">Hours <span className="text-red-500">*</span></Label>
                            <Input 
                                id="hours" 
                                type="number" 
                                step="0.25" 
                                min="0" 
                                max="24"
                                placeholder="e.g. 8.0" 
                                value={formData.hours}
                                onChange={(e) => setFormData({...formData, hours: e.target.value})}
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea 
                                id="notes" 
                                placeholder="Brief description of work done..."
                                value={formData.notes}
                                onChange={(e) => setFormData({...formData, notes: e.target.value})}
                            />
                        </div>

                        <Button type="submit" className="w-full bg-[#003D82] font-bold" disabled={loading}>
                            {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Save className="w-4 h-4 mr-2" />}
                            Save Entry
                        </Button>
                    </form>
                </CardContent>
            </Card>

            {/* History Table */}
            <Card className="lg:col-span-2 shadow-lg">
                <CardHeader>
                    <CardTitle>Time Sheet History</CardTitle>
                    <CardDescription>Recent log entries (Sorted by date).</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm text-left">
                            <thead className="bg-gray-50 text-gray-700 font-semibold border-b">
                                <tr>
                                    <th className="px-4 py-3">Date</th>
                                    <th className="px-4 py-3">Activity</th>
                                    <th className="px-4 py-3">Hours</th>
                                    <th className="px-4 py-3">Notes</th>
                                    <th className="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {entries.length === 0 ? (
                                    <tr>
                                        <td colSpan="5" className="px-4 py-8 text-center text-gray-500">No entries recorded yet.</td>
                                    </tr>
                                ) : (
                                    entries.map((entry) => {
                                        // Try to find activity name from ID, or use stored name
                                        const act = activities.find(a => a.id === entry.activity_id);
                                        const actName = act ? (act.activity_name || act.name) : (entry.activity_name || 'Unknown');
                                        
                                        return (
                                            <tr key={entry.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 whitespace-nowrap font-medium text-[#003D82]">
                                                    {format(new Date(entry.date), 'MMM dd, yyyy')}
                                                </td>
                                                <td className="px-4 py-3 text-gray-700">{actName}</td>
                                                <td className="px-4 py-3 font-bold">{parseFloat(entry.hours).toFixed(2)}</td>
                                                <td className="px-4 py-3 text-gray-500 max-w-[150px] truncate">{entry.notes || '-'}</td>
                                                <td className="px-4 py-3 text-right space-x-1">
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm" 
                                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-800"
                                                        onClick={() => handleEditClick(entry)}
                                                    >
                                                        <Edit2 className="w-4 h-4" />
                                                    </Button>
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm" 
                                                        className="h-8 w-8 p-0 text-red-500 hover:text-red-700"
                                                        onClick={() => handleDelete(entry.id)}
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        {/* Edit Modal */}
        <Dialog open={editModalOpen} onOpenChange={setEditModalOpen}>
            <DialogContent>
                <DialogHeader><DialogTitle>Edit Time Entry</DialogTitle></DialogHeader>
                {editingEntry && (
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label>Date</Label>
                            <Input 
                                type="date" 
                                value={editingEntry.date} 
                                onChange={(e) => setEditingEntry({...editingEntry, date: e.target.value})}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Activity</Label>
                            <Select 
                                value={editingEntry.activity_id} 
                                onValueChange={(val) => setEditingEntry({...editingEntry, activity_id: val})}
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {activities.map(a => (
                                        <SelectItem key={a.id} value={a.id}>{a.activity_name || a.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Hours</Label>
                            <Input 
                                type="number" 
                                step="0.25"
                                value={editingEntry.hours} 
                                onChange={(e) => setEditingEntry({...editingEntry, hours: e.target.value})}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Notes</Label>
                            <Textarea 
                                value={editingEntry.notes || ''} 
                                onChange={(e) => setEditingEntry({...editingEntry, notes: e.target.value})}
                            />
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setEditModalOpen(false)}>Cancel</Button>
                            <Button className="bg-[#003D82]" onClick={handleUpdate}>Save Changes</Button>
                        </DialogFooter>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    </div>
  );
};

export default FillTimeSheetPage;