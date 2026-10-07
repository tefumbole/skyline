import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_NAV } from '@/config/eventNavConfig';
import { createEvent } from '@/services/eventService';
import { getAllMeals } from '@/services/mealsService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/use-toast';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, Image as ImageIcon } from 'lucide-react';

const CreateEventPage = () => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    date: '',
    time: '',
    location: '',
    banner_url: '',
  });
  const [specifyMeals, setSpecifyMeals] = useState(false);
  const [availableMeals, setAvailableMeals] = useState([]);
  const [selectedMealIds, setSelectedMealIds] = useState([]);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { toast } = useToast();

  useEffect(() => {
    getAllMeals().then(setAvailableMeals).catch(() => setAvailableMeals([]));
  }, []);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const toggleMeal = (id) => {
    setSelectedMealIds((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
    );
  };

  const selectAllMeals = () => {
    setSelectedMealIds(availableMeals.map((m) => m.id));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    const payload = {
      ...formData,
      specify_meals: specifyMeals,
      meals_json: specifyMeals
        ? availableMeals.filter((m) => selectedMealIds.includes(m.id))
        : [],
    };

    const res = await createEvent(payload);
    if (res.success) {
      toast({ title: 'Event created successfully!' });
      navigate('/admin/events');
    } else {
      toast({ title: 'Failed to create event', description: res.error, variant: 'destructive' });
    }
    setLoading(false);
  };

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <AdminHorizontalNav items={EVENT_NAV} title="Create Event" />

      <Card>
        <CardHeader>
          <CardTitle>Event Details</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-5">
            <div className="space-y-2">
              <Label htmlFor="name">Event Name *</Label>
              <Input id="name" name="name" required value={formData.name} onChange={handleChange} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea id="description" name="description" value={formData.description} onChange={handleChange} rows={4} />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div className="space-y-2">
                <Label htmlFor="date">Event Date *</Label>
                <Input id="date" name="date" type="date" required value={formData.date} onChange={handleChange} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="time">Event Time *</Label>
                <Input id="time" name="time" type="time" required value={formData.time} onChange={handleChange} />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="location">Location</Label>
              <Input id="location" name="location" value={formData.location} onChange={handleChange} />
            </div>

            <div className="space-y-2">
              <Label htmlFor="banner_url">Banner Image URL</Label>
              <div className="relative">
                <ImageIcon className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <Input id="banner_url" name="banner_url" value={formData.banner_url} onChange={handleChange} className="pl-9" />
              </div>
            </div>

            <div className="border rounded-lg p-4 space-y-3">
              <div className="flex items-center gap-2">
                <Checkbox id="specify_meals" checked={specifyMeals} onCheckedChange={setSpecifyMeals} />
                <Label htmlFor="specify_meals">Specify Meals</Label>
              </div>
              {specifyMeals && (
                <div className="space-y-2">
                  <div className="flex justify-between items-center">
                    <p className="text-sm text-gray-600">Select meals available for this event:</p>
                    <Button type="button" variant="outline" size="sm" onClick={selectAllMeals}>Select All</Button>
                  </div>
                  {availableMeals.length === 0 ? (
                    <p className="text-sm text-amber-600">No meals found. <a href="/admin/events/meals/create" className="underline">Create meals</a> first.</p>
                  ) : (
                    <div className="grid sm:grid-cols-2 gap-2">
                      {availableMeals.map((meal) => (
                        <label key={meal.id} className="flex items-center gap-2 border rounded p-2 cursor-pointer hover:bg-gray-50">
                          <Checkbox checked={selectedMealIds.includes(meal.id)} onCheckedChange={() => toggleMeal(meal.id)} />
                          <span className="text-sm">{meal.name}</span>
                        </label>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>

            <div className="pt-4 border-t flex justify-end gap-3">
              <Button type="button" variant="outline" onClick={() => navigate('/admin/events')}>Cancel</Button>
              <Button type="submit" disabled={loading} className="bg-[#003D82] text-white">
                {loading ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : null}
                Save Event
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
};

export default CreateEventPage;
