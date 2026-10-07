import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Save } from 'lucide-react';
import { saveMessageAsTemplate } from '@/services/templateService';

const SaveTemplateModal = ({ isOpen, onClose, messageData, onSaveSuccess }) => {
  const [templateName, setTemplateName] = useState('');
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  const handleSave = async () => {
    if (!templateName.trim()) {
      toast({
        title: "Validation Error",
        description: "Template name is required.",
        variant: "destructive"
      });
      return;
    }

    setLoading(true);
    const result = await saveMessageAsTemplate({
      name: templateName,
      subject: messageData.subject,
      body: messageData.body,
      category: messageData.category,
      send_email: messageData.sendEmail,
      send_whatsapp: messageData.sendWhatsapp,
      generate_pdf: messageData.generatePdf
    });

    if (result.success) {
      toast({
        title: "Template Saved",
        description: "Your message has been saved as a template successfully.",
      });
      setTemplateName('');
      onSaveSuccess && onSaveSuccess(result.data);
      onClose();
    } else {
      toast({
        title: "Error saving template",
        description: result.error,
        variant: "destructive"
      });
    }
    setLoading(false);
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Save as Template</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="space-y-2">
            <Label htmlFor="templateName">Template Name</Label>
            <Input
              id="templateName"
              placeholder="e.g. Monthly Newsletter Template"
              value={templateName}
              onChange={(e) => setTemplateName(e.target.value)}
            />
          </div>

          <div className="bg-gray-50 p-3 rounded-md border text-sm">
            <p className="font-semibold text-gray-700 mb-1">Preview Content:</p>
            <p className="text-gray-900 font-medium truncate mb-1">Subject: {messageData.subject || '(No Subject)'}</p>
            <p className="text-gray-500 line-clamp-3">{messageData.body || '(No Body)'}</p>
          </div>
        </div>

        <div className="flex justify-end space-x-2">
          <Button variant="outline" onClick={onClose} disabled={loading}>Cancel</Button>
          <Button onClick={handleSave} disabled={loading} className="bg-[#003D82] text-white">
            {loading ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Save className="w-4 h-4 mr-2" />}
            Save Template
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default SaveTemplateModal;