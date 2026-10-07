import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_TEMPLATES_NAV } from '@/config/eventTemplatesNavConfig';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Plus, Trash2, Copy, Check, TestTube, Webhook } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const AVAILABLE_EVENTS = [
  { id: 'invitation.created', label: 'Invitation Created' },
  { id: 'invitation.sent', label: 'Invitation Sent' },
  { id: 'invitation.opened', label: 'Invitation Opened' },
  { id: 'rsvp.received', label: 'RSVP Received' },
  { id: 'event.created', label: 'Event Created' },
  { id: 'event.updated', label: 'Event Updated' }
];

const WebhookSettingsPage = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  
  const [webhooks, setWebhooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [testingId, setTestingId] = useState(null);
  const [copiedId, setCopiedId] = useState(null);
  
  const [formData, setFormData] = useState({
    webhook_url: '',
    triggers: ['invitation.created'],
    enabled: true
  });

  const fetchWebhooks = async () => {
    if (!user) return;
    setLoading(true);
    try {
      const { data, error } = await supabase
        .from('webhook_settings')
        .select('*')
        .eq('user_id', user.id)
        .order('created_at', { ascending: false });

      if (error) throw error;
      setWebhooks(data || []);
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchWebhooks();
  }, [user]);

  const handleCopy = (id, text) => {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
    toast({ title: 'URL copied to clipboard' });
  };

  const handleTest = async (webhook) => {
    setTestingId(webhook.id);
    try {
      const payload = {
        event: 'test.ping',
        timestamp: new Date().toISOString(),
        data: { message: 'This is a test webhook from Hostinger Horizons Events.' }
      };

      const res = await fetch(webhook.webhook_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        toast({ title: 'Test Successful', description: `Server responded with status ${res.status}` });
      } else {
        toast({ title: 'Test Failed', description: `Server responded with status ${res.status}`, variant: 'destructive' });
      }
    } catch (err) {
      toast({ title: 'Connection Error', description: err.message, variant: 'destructive' });
    } finally {
      setTestingId(null);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this webhook?')) return;
    
    try {
      const { error } = await supabase.from('webhook_settings').delete().eq('id', id);
      if (error) throw error;
      
      toast({ title: 'Webhook deleted successfully' });
      setWebhooks(webhooks.filter(w => w.id !== id));
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    }
  };

  const toggleEvent = (eventId) => {
    setFormData(prev => {
      const current = prev.triggers || [];
      const updated = current.includes(eventId)
        ? current.filter(id => id !== eventId)
        : [...current, eventId];
      return { ...prev, triggers: updated };
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.webhook_url || !formData.webhook_url.startsWith('http')) {
      toast({ title: 'Invalid URL', description: 'Webhook URL must start with http:// or https://', variant: 'destructive' });
      return;
    }
    if (!formData.triggers || formData.triggers.length === 0) {
      toast({ title: 'Validation Error', description: 'Please select at least one event trigger.', variant: 'destructive' });
      return;
    }

    setIsSubmitting(true);
    try {
      const { data, error } = await supabase
        .from('webhook_settings')
        .insert([{
          user_id: user.id,
          webhook_url: formData.webhook_url,
          triggers: formData.triggers,
          enabled: formData.enabled
        }])
        .select()
        .single();

      if (error) throw error;
      
      toast({ title: 'Webhook added successfully' });
      setWebhooks([data, ...webhooks]);
      setIsModalOpen(false);
      setFormData({ webhook_url: '', triggers: ['invitation.created'], enabled: true });
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      <AdminHorizontalNav
        items={EVENT_TEMPLATES_NAV}
        title="Templates & Config"
        description="Manage invitation design templates, WhatsApp messages, and webhooks."
      />
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-xl font-bold text-[#003D82] flex items-center">
            <Webhook className="w-5 h-5 mr-2 text-[#D4AF37]" />
            Webhook Settings
          </h2>
          <p className="text-gray-500 mt-1">Configure webhooks to receive real-time event notifications.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} className="bg-[#003D82] hover:bg-blue-800 text-white">
          <Plus className="w-4 h-4 mr-2" /> Add Webhook
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-4">
          {loading ? (
            <div className="flex flex-col items-center justify-center py-20 text-gray-400 bg-white rounded-xl border border-gray-200">
              <Loader2 className="w-8 h-8 animate-spin mb-4" />
              <p>Loading webhooks...</p>
            </div>
          ) : webhooks.length === 0 ? (
            <div className="bg-white border-2 border-dashed border-gray-200 rounded-xl py-16 flex flex-col items-center text-center px-4">
              <div className="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                <Webhook className="w-8 h-8 text-[#003D82]" />
              </div>
              <h3 className="text-lg font-bold text-gray-900 mb-2">No webhooks configured yet</h3>
              <p className="text-gray-500 max-w-md mb-6">
                Add your first webhook to receive real-time data pushes to your own systems.
              </p>
              <Button onClick={() => setIsModalOpen(true)} variant="outline" className="border-[#003D82] text-[#003D82]">
                <Plus className="w-4 h-4 mr-2" /> Add First Webhook
              </Button>
            </div>
          ) : (
            <div className="space-y-4">
              {webhooks.map(webhook => (
                <Card key={webhook.id} className="shadow-sm">
                  <CardHeader className="pb-3 border-b border-gray-100 flex flex-row items-center justify-between">
                    <div className="space-y-1">
                      <CardTitle className="text-base font-semibold font-mono text-gray-800 truncate max-w-[400px]">
                        {webhook.webhook_url}
                      </CardTitle>
                      <div className="flex flex-wrap gap-1">
                        {(webhook.triggers || []).map(trigger => (
                          <Badge key={trigger} variant="outline" className="text-xs bg-gray-50 text-gray-600">
                            {trigger}
                          </Badge>
                        ))}
                      </div>
                    </div>
                    <Badge variant={webhook.enabled ? "default" : "secondary"} className={webhook.enabled ? "bg-green-100 text-green-800 hover:bg-green-200" : ""}>
                      {webhook.enabled ? 'Active' : 'Inactive'}
                    </Badge>
                  </CardHeader>
                  <CardFooter className="pt-3 bg-gray-50 rounded-b-xl flex justify-between">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" onClick={() => handleTest(webhook)} disabled={testingId === webhook.id} className="bg-white">
                        {testingId === webhook.id ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <TestTube className="w-4 h-4 mr-2 text-blue-600" />}
                        Test
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => handleCopy(webhook.id, webhook.webhook_url)} className="bg-white">
                        {copiedId === webhook.id ? <Check className="w-4 h-4 mr-2 text-green-600" /> : <Copy className="w-4 h-4 mr-2" />}
                        Copy URL
                      </Button>
                    </div>
                    <Button variant="ghost" size="sm" className="text-red-500 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(webhook.id)}>
                      <Trash2 className="w-4 h-4 mr-2" /> Delete
                    </Button>
                  </CardFooter>
                </Card>
              ))}
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <Card className="bg-[#003D82] text-white shadow-md border-none">
            <CardHeader>
              <CardTitle className="flex items-center text-lg">
                <TestTube className="w-5 h-5 mr-2 text-[#D4AF37]" /> Documentation
              </CardTitle>
              <CardDescription className="text-blue-100">Webhook payload format</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-blue-100 mb-4">
                When an event occurs, a POST request is sent to your configured URL with a JSON payload:
              </p>
              <div className="bg-black/30 rounded-md p-4 overflow-x-auto text-xs font-mono text-green-300">
<pre>{`{
  "event": "invitation.created",
  "timestamp": "2026-03-16T12:00:00Z",
  "data": {
    "invitation_id": "uuid",
    "event_id": "uuid",
    "guest_name": "John Doe",
    "status": "pending"
  }
}`}</pre>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>Add New Webhook</DialogTitle>
            <DialogDescription>Enter a valid URL to receive event payloads.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-6 py-2">
            <div className="space-y-2">
              <Label htmlFor="url">Webhook URL *</Label>
              <Input 
                id="url" 
                type="url"
                value={formData.webhook_url} 
                onChange={e => setFormData({...formData, webhook_url: e.target.value})} 
                placeholder="https://your-server.com/webhooks" 
                required
              />
            </div>
            
            <div className="space-y-3">
              <Label>Events to Subscribe *</Label>
              <div className="grid grid-cols-1 gap-2 p-4 bg-gray-50 rounded-lg border border-gray-200">
                {AVAILABLE_EVENTS.map(ev => (
                  <div key={ev.id} className="flex items-center space-x-2">
                    <Checkbox 
                      id={`event-${ev.id}`} 
                      checked={formData.triggers.includes(ev.id)}
                      onCheckedChange={() => toggleEvent(ev.id)}
                    />
                    <Label htmlFor={`event-${ev.id}`} className="font-normal cursor-pointer text-sm">
                      <span className="font-mono text-gray-500 mr-2">{ev.id}</span>
                      {ev.label}
                    </Label>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex items-center space-x-2">
              <Checkbox 
                id="active" 
                checked={formData.enabled}
                onCheckedChange={c => setFormData({...formData, enabled: c})}
              />
              <Label htmlFor="active" className="cursor-pointer">Enable Webhook immediately</Label>
            </div>

            <DialogFooter className="pt-4 border-t">
              <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)} disabled={isSubmitting}>
                Cancel
              </Button>
              <Button type="submit" className="bg-[#003D82] text-white hover:bg-blue-800" disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
                Save Webhook
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default WebhookSettingsPage;