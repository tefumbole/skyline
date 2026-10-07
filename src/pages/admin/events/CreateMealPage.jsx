import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_NAV } from '@/config/eventNavConfig';
import { createMeal } from '@/services/mealsService';
import { supabase } from '@/lib/customSupabaseClient';
import { getStoragePublicUrl } from '@/utils/storageUrl';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, ImagePlus } from 'lucide-react';

const CreateMealPage = () => {
  const [form, setForm] = useState({ name: '', description: '', category: 'General', image_url: '' });
  const [imagePreview, setImagePreview] = useState('');
  const [uploadingImage, setUploadingImage] = useState(false);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { toast } = useToast();

  const handleImageUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      toast({ title: 'Invalid file', description: 'Please choose an image file.', variant: 'destructive' });
      return;
    }

    setUploadingImage(true);
    try {
      const ext = file.name.split('.').pop() || 'jpg';
      const filePath = `meal-${Date.now()}.${ext}`;
      const { data, error } = await supabase.storage.from('event-meals').upload(filePath, file, {
        contentType: file.type,
        upsert: true,
      });
      if (error) throw error;

      const storedPath = data?.path || data?.Key || filePath;
      const publicUrl = getStoragePublicUrl('event-meals', storedPath);
      setForm((prev) => ({ ...prev, image_url: publicUrl }));
      setImagePreview(publicUrl);
      toast({ title: 'Image uploaded' });
    } catch (err) {
      toast({ title: 'Upload failed', description: err.message, variant: 'destructive' });
    } finally {
      setUploadingImage(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.name.trim()) return;
    setLoading(true);
    try {
      await createMeal(form);
      toast({ title: 'Meal created' });
      navigate('/admin/events/meals');
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    }
    setLoading(false);
  };

  return (
    <div className="max-w-2xl">
      <AdminHorizontalNav items={EVENT_NAV} title="Create Meal" />
      <Card>
        <CardHeader><CardTitle>Meal Details</CardTitle></CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <Label>Name *</Label>
              <Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </div>
            <div>
              <Label>Category</Label>
              <Input value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} />
            </div>
            <div>
              <Label>Description</Label>
              <Textarea rows={3} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Meal Photo</Label>
              <div className="flex items-center gap-4">
                <label className="inline-flex items-center gap-2 px-4 py-2 border rounded-md cursor-pointer hover:bg-gray-50">
                  {uploadingImage ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <ImagePlus className="w-4 h-4" />
                  )}
                  <span>{uploadingImage ? 'Uploading…' : 'Choose image'}</span>
                  <input type="file" accept="image/*" className="hidden" onChange={handleImageUpload} disabled={uploadingImage} />
                </label>
                {imagePreview && (
                  <img src={imagePreview} alt="Meal preview" className="h-16 w-16 rounded-lg object-cover border" />
                )}
              </div>
              <p className="text-xs text-gray-500">Add a photo so guests can identify this meal easily.</p>
            </div>
            <Button type="submit" disabled={loading || uploadingImage} className="bg-[#003D82]">
              {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null} Save Meal
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
};

export default CreateMealPage;
