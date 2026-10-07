import React, { useState, useEffect } from 'react';
import { updateCourse } from '@/services/coursesService';
import { parseCurriculumJson } from '@/utils/trainingCourseUtils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Save, Plus, Trash2 } from 'lucide-react';

const CATEGORIES = ['Training', 'IT Services', 'Security & Surveillance', 'Audio & Visual', 'Maintenance & Support', 'Other'];
const ICONS = ['Brain', 'Cloud', 'Shield', 'Briefcase', 'Phone', 'Network', 'Video'];

const EditCourseForm = ({ course, isOpen, onClose, onSuccess }) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    price: '',
    category: 'Training',
    duration: '',
    delivery_mode: '',
    icon: 'Briefcase',
    color: '#003D82',
    sections: [],
  });
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (!course) return;
    const curriculum = parseCurriculumJson(course.curriculum_json);
    setFormData({
      name: course.name || '',
      description: course.description || '',
      price: course.price ?? '',
      category: course.category || 'Training',
      duration: course.duration || '',
      delivery_mode: course.delivery_mode || '',
      icon: course.icon || 'Briefcase',
      color: course.color || '#003D82',
      sections: (curriculum.sections || []).map((s) => ({
        title: s.title || '',
        itemsText: (s.items || []).join('\n'),
      })),
    });
  }, [course]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const updateSection = (index, field, value) => {
    setFormData((prev) => {
      const sections = [...prev.sections];
      sections[index] = { ...sections[index], [field]: value };
      return { ...prev, sections };
    });
  };

  const addSection = () => {
    setFormData((prev) => ({
      ...prev,
      sections: [...prev.sections, { title: 'New Section', itemsText: '' }],
    }));
  };

  const removeSection = (index) => {
    setFormData((prev) => ({
      ...prev,
      sections: prev.sections.filter((_, i) => i !== index),
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) {
      toast({ title: 'Validation Error', description: 'Course name is required.', variant: 'destructive' });
      return;
    }

    setLoading(true);
    try {
      const sections = formData.sections.map((s) => ({
        title: s.title,
        items: s.itemsText
          .split('\n')
          .map((line) => line.trim())
          .filter(Boolean),
      }));

      await updateCourse(course.id, {
        name: formData.name,
        description: formData.description,
        price: formData.price,
        category: formData.category,
        duration: formData.duration,
        delivery_mode: formData.delivery_mode,
        icon: formData.icon,
        color: formData.color,
        curriculum_json: { sections },
      });

      toast({ title: 'Success', description: 'Course updated. Frontend training page will reflect changes.' });
      onSuccess?.();
      onClose();
    } catch (error) {
      toast({ title: 'Error', description: error.message || 'Failed to update course.', variant: 'destructive' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Course — {course?.name}</DialogTitle>
          <DialogDescription>
            Update course details and curriculum outline shown on the public Training page.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4 py-2">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="name">Course Name</Label>
              <Input id="name" name="name" value={formData.name} onChange={handleChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="price">Price (USD)</Label>
              <Input id="price" name="price" type="number" min="0" step="0.01" value={formData.price} onChange={handleChange} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="duration">Duration</Label>
              <Input id="duration" name="duration" value={formData.duration} onChange={handleChange} placeholder="e.g. 12 weeks" />
            </div>
            <div className="space-y-2">
              <Label htmlFor="delivery_mode">Delivery Mode</Label>
              <Input id="delivery_mode" name="delivery_mode" value={formData.delivery_mode} onChange={handleChange} />
            </div>
            <div className="space-y-2">
              <Label>Category</Label>
              <Select value={formData.category} onValueChange={(v) => setFormData((p) => ({ ...p, category: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {CATEGORIES.map((cat) => <SelectItem key={cat} value={cat}>{cat}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Icon</Label>
              <Select value={formData.icon} onValueChange={(v) => setFormData((p) => ({ ...p, icon: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {ICONS.map((icon) => <SelectItem key={icon} value={icon}>{icon}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="color">Theme Color</Label>
              <Input id="color" name="color" type="color" value={formData.color} onChange={handleChange} className="h-10 w-24" />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea id="description" name="description" value={formData.description} onChange={handleChange} rows={2} />
          </div>

          <div className="space-y-3 border-t pt-4">
            <div className="flex items-center justify-between">
              <Label className="text-base font-semibold">Curriculum Outline</Label>
              <Button type="button" variant="outline" size="sm" onClick={addSection}>
                <Plus className="w-4 h-4 mr-1" /> Add Section
              </Button>
            </div>

            {formData.sections.map((section, index) => (
              <div key={index} className="border rounded-lg p-4 space-y-2 bg-gray-50">
                <div className="flex gap-2 items-center">
                  <Input
                    value={section.title}
                    onChange={(e) => updateSection(index, 'title', e.target.value)}
                    placeholder="Section title"
                    className="bg-white"
                  />
                  <Button type="button" variant="ghost" size="icon" onClick={() => removeSection(index)}>
                    <Trash2 className="w-4 h-4 text-red-500" />
                  </Button>
                </div>
                <Textarea
                  value={section.itemsText}
                  onChange={(e) => updateSection(index, 'itemsText', e.target.value)}
                  placeholder="One curriculum item per line"
                  rows={4}
                  className="bg-white font-mono text-sm"
                />
              </div>
            ))}
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose} disabled={loading}>Cancel</Button>
            <Button type="submit" disabled={loading} className="bg-[#003D82] hover:bg-[#002d62]">
              {loading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Save className="mr-2 h-4 w-4" />}
              Save Changes
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default EditCourseForm;
