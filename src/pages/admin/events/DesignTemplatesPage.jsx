import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { EVENT_TEMPLATES_NAV } from '@/config/eventTemplatesNavConfig';
import { getAllTemplates, deleteInvitationTemplate, toggleTemplateStatus } from '@/services/templateService';
import TemplateUploadForm from '@/components/TemplateUploadForm';
import TemplateList from '@/components/TemplateList';
import TemplatePreviewModal from '@/components/TemplatePreviewModal';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/use-toast';
import { PlusCircle, Loader2, AlertCircle, LayoutTemplate } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const DesignTemplatesPage = () => {
  const { user } = useAuth();
  const { toast } = useToast();
  
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [previewTemplate, setPreviewTemplate] = useState(null);

  const fetchTemplates = async () => {
    if (!user) return;
    setLoading(true);
    setError(null);
    try {
      const res = await getAllTemplates(user.id);
      if (!res.success) throw new Error(res.error);
      setTemplates(res.data);
    } catch (err) {
      setError(err.message || 'Failed to load templates');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTemplates();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user]);

  const handleDelete = async (template) => {
    try {
      const res = await deleteInvitationTemplate(template.id, template.background_image_path);
      if (!res.success) throw new Error(res.error);
      
      toast({ title: 'Deleted', description: 'Template removed successfully.' });
      setTemplates(templates.filter(t => t.id !== template.id));
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    }
  };

  const handleToggleStatus = async (template) => {
    try {
      const res = await toggleTemplateStatus(template.id, template.status);
      if (!res.success) throw new Error(res.error);
      
      toast({ title: 'Status Updated', description: `Template is now ${res.data.status}.` });
      setTemplates(templates.map(t => t.id === template.id ? res.data : t));
    } catch (err) {
      toast({ title: 'Error', description: err.message, variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6 p-6">
      <AdminHorizontalNav
        items={EVENT_TEMPLATES_NAV}
        title="Templates & Config"
        description="Manage invitation design templates, WhatsApp messages, and webhooks."
      />
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-xl font-bold text-[#003D82] flex items-center">
            <LayoutTemplate className="w-5 h-5 mr-2 text-[#D4AF37]" />
            Design Templates
          </h2>
          <p className="text-gray-500 mt-1">Manage your invitation background templates.</p>
        </div>
        <Button onClick={() => setIsUploadModalOpen(true)} className="bg-[#003D82] text-white hover:bg-blue-800">
          <PlusCircle className="w-4 h-4 mr-2" /> Upload Template
        </Button>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-20 text-gray-400">
          <Loader2 className="w-8 h-8 animate-spin mb-4" />
          <p>Loading templates...</p>
        </div>
      ) : error ? (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      ) : templates.length === 0 ? (
        <div className="bg-white border-2 border-dashed border-gray-200 rounded-xl py-16 flex flex-col items-center text-center px-4">
          <div className="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
            <LayoutTemplate className="w-8 h-8 text-[#003D82]" />
          </div>
          <h3 className="text-lg font-bold text-gray-900 mb-2">No templates found</h3>
          <p className="text-gray-500 max-w-md mb-6">
            You haven't uploaded any design templates yet. Upload your first background image to use it for digital invitations.
          </p>
          <Button onClick={() => setIsUploadModalOpen(true)} variant="outline" className="border-[#003D82] text-[#003D82]">
            <PlusCircle className="w-4 h-4 mr-2" /> Upload Your First Template
          </Button>
        </div>
      ) : (
        <TemplateList 
          templates={templates} 
          onPreview={setPreviewTemplate} 
          onToggleStatus={handleToggleStatus}
          onDelete={handleDelete}
        />
      )}

      {/* Modals */}
      <TemplateUploadForm 
        isOpen={isUploadModalOpen} 
        onClose={() => setIsUploadModalOpen(false)} 
        onSuccess={fetchTemplates}
      />
      
      <TemplatePreviewModal 
        template={previewTemplate} 
        isOpen={!!previewTemplate} 
        onClose={() => setPreviewTemplate(null)} 
      />
    </div>
  );
};

export default DesignTemplatesPage;