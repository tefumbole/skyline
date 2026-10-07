import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

const TemplatePreviewModal = ({ template, isOpen, onClose }) => {
  if (!template) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[700px] p-0 overflow-hidden">
        <div className="relative">
          {/* Full Image */}
          <img 
            src={template.background_image_url} 
            alt={template.name} 
            className="w-full h-[400px] object-cover"
          />
          
          {/* Information Overlay at bottom */}
          <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 pt-12">
            <div className="flex items-center gap-3 mb-2">
              <h2 className="text-2xl font-bold text-white">{template.name}</h2>
              <Badge variant="outline" className="text-white border-white/40 bg-white/10">
                {template.category}
              </Badge>
              <Badge className={template.status === 'active' ? "bg-green-500/80 text-white" : "bg-gray-500/80 text-white"}>
                {template.status}
              </Badge>
            </div>
            {template.description && (
              <p className="text-gray-200 text-sm">
                {template.description}
              </p>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default TemplatePreviewModal;