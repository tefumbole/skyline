import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_TEMPLATES_NAV } from '@/config/eventTemplatesNavConfig';
import { getTemplates, saveMessageAsTemplate, deleteTemplate } from '@/services/templateService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Plus, Trash2, Copy, Check, MessageSquare } from 'lucide-react';

const WhatsAppTemplatesPage = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [copiedId, setCopiedId] = useState(null);
  
  const [formData, setFormData] = useState({
    name: '',
    category: 'general',
    body: ''
  });

  const fetchTemplates = async () => {
    if (!user) return;
    setLoading(true);
    try {
      const res = await getTemplates(user.id);
      if (res.success) {
        setTemplates(res.data);
      } else {
        toast({ title: 'Error loading templates', description: res.error, variant: 'destructive' });
      }
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTemplates();
  }, [user]);

  const handleCopy = (id, text) => {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
    toast({ title: 'Copied to clipboard' });
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Are you sure you want to delete this template?')) return;
    
    try {
      const res = await deleteTemplate(id);
      if (res.success) {
        toast({ title: 'Template deleted successfully' });
        setTemplates(templates.filter(t => t.id !== id));
      } else {
        toast({ title: 'Failed to delete', description: res.error, variant: 'destructive' });
      }
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!formData.name.trim() || !formData.body.trim()) {
      toast({ title: 'Validation Error', description: 'Name and Message are required.', variant: 'destructive' });
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await saveMessageAsTemplate({
        name: formData.name.trim(),
        category: formData.category,
        body: formData.body.trim(),
        send_whatsapp: true,
        send_email: false,
        generate_pdf: false
      });

      if (res.success) {
        toast({ title: 'Template created successfully' });
        setTemplates([res.data, ...templates]);
        setIsModalOpen(false);
        setFormData({ name: '', category: 'general', body: '' });
      } else {
        toast({ title: 'Failed to create', description: res.error, variant: 'destructive' });
      }
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    } finally {
      setIsSubmitting(false);
    }
  };

  const getCategoryColor = (cat) => {
    const colors = {
      general: 'bg-gray-100 text-gray-800',
      reminder: 'bg-blue-100 text-blue-800',
      confirmation: 'bg-green-100 text-green-800',
      invitation: 'bg-purple-100 text-purple-800',
      followup: 'bg-orange-100 text-orange-800'
    };
    return colors[cat?.toLowerCase()] || colors.general;
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
            <MessageSquare className="w-5 h-5 mr-2 text-[#D4AF37]" />
            WhatsApp Message Templates
          </h2>
          <p className="text-gray-500 mt-1">Create and manage reusable WhatsApp messages for your events.</p>
        </div>
        <Button onClick={() => setIsModalOpen(true)} className="bg-[#003D82] hover:bg-blue-800 text-white">
          <Plus className="w-4 h-4 mr-2" /> New Template
        </Button>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-20 text-gray-400">
          <Loader2 className="w-8 h-8 animate-spin mb-4" />
          <p>Loading templates...</p>
        </div>
      ) : templates.length === 0 ? (
        <div className="bg-white border-2 border-dashed border-gray-200 rounded-xl py-16 flex flex-col items-center text-center px-4">
          <div className="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
            <MessageSquare className="w-8 h-8 text-[#003D82]" />
          </div>
          <h3 className="text-lg font-bold text-gray-900 mb-2">No templates yet</h3>
          <p className="text-gray-500 max-w-md mb-6">
            Create your first template to get started with standardized WhatsApp messaging.
          </p>
          <Button onClick={() => setIsModalOpen(true)} variant="outline" className="border-[#003D82] text-[#003D82]">
            <Plus className="w-4 h-4 mr-2" /> Create First Template
          </Button>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {templates.map(template => (
            <Card key={template.id} className="flex flex-col h-full shadow-sm hover:shadow-md transition-shadow">
              <CardHeader className="pb-3 border-b border-gray-100">
                <div className="flex justify-between items-start">
                  <CardTitle className="text-lg font-semibold text-gray-900 line-clamp-1">{template.name}</CardTitle>
                  <Badge variant="secondary" className={getCategoryColor(template.category)}>
                    {template.category}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="pt-4 flex-grow">
                <div className="bg-gray-50 rounded-md p-3 text-sm text-gray-700 whitespace-pre-wrap line-clamp-4 font-mono">
                  {template.body}
                </div>
              </CardContent>
              <CardFooter className="pt-0 border-t border-gray-100 pt-3 flex justify-between">
                <Button variant="ghost" size="sm" className="text-gray-600 hover:text-[#003D82]" onClick={() => handleCopy(template.id, template.body)}>
                  {copiedId === template.id ? <Check className="w-4 h-4 mr-2 text-green-600" /> : <Copy className="w-4 h-4 mr-2" />}
                  {copiedId === template.id ? 'Copied' : 'Copy'}
                </Button>
                <Button variant="ghost" size="sm" className="text-red-500 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(template.id)}>
                  <Trash2 className="w-4 h-4 mr-2" /> Delete
                </Button>
              </CardFooter>
            </Card>
          ))}
        </div>
      )}

      <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
        <DialogContent className="sm:max-w-[600px]">
          <DialogHeader>
            <DialogTitle>Create WhatsApp Template</DialogTitle>
            <DialogDescription>Define a reusable message for your communications.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4 py-2">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name">Template Name *</Label>
                <Input 
                  id="name" 
                  value={formData.name} 
                  onChange={e => setFormData({...formData, name: e.target.value})} 
                  placeholder="e.g. Event Reminder" 
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="category">Category</Label>
                <Select value={formData.category} onValueChange={v => setFormData({...formData, category: v})}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select Category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="general">General</SelectItem>
                    <SelectItem value="invitation">Invitation</SelectItem>
                    <SelectItem value="reminder">Reminder</SelectItem>
                    <SelectItem value="confirmation">Confirmation</SelectItem>
                    <SelectItem value="followup">Follow Up</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="body">Message Content *</Label>
              <Textarea 
                id="body" 
                value={formData.body} 
                onChange={e => setFormData({...formData, body: e.target.value})} 
                placeholder="Hello {{name}}, this is a reminder for..." 
                className="min-h-[150px] font-mono text-sm"
                required
              />
              <p className="text-xs text-gray-500">
                Tip: You can use standard placeholders like <code className="bg-gray-100 px-1 rounded">{'{name}'}</code>, <code className="bg-gray-100 px-1 rounded">{'{date}'}</code>, <code className="bg-gray-100 px-1 rounded">{'{time}'}</code> for dynamic data.
              </p>
            </div>
            <DialogFooter className="pt-4 border-t">
              <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)} disabled={isSubmitting}>
                Cancel
              </Button>
              <Button type="submit" className="bg-[#003D82] text-white" disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
                Save Template
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default WhatsAppTemplatesPage;