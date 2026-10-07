import React, { useState, useEffect } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Loader2, Download, X } from 'lucide-react';
import { generateMessagePDFHTML } from '@/services/pdfGenerationService';
import html2pdf from 'html2pdf.js';

const PDFPreviewModal = ({ isOpen, onClose, messageData, recipientData, settings }) => {
  const [htmlContent, setHtmlContent] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (isOpen && messageData) {
      generatePreview();
    }
  }, [isOpen, messageData, recipientData]);

  const generatePreview = async () => {
    setLoading(true);
    try {
      const mockRecipient = recipientData || {
        name: 'John Doe',
        email: 'john.doe@example.com',
        phone: '+1234567890',
        reference_code: 'PREVIEW-REF-123',
        verification_url: 'https://example.com/verify/PREVIEW-REF-123'
      };

      const html = await generateMessagePDFHTML(messageData, mockRecipient, settings || {});
      setHtmlContent(html);
    } catch (error) {
      console.error('Error generating preview:', error);
      setHtmlContent('<div style="padding: 20px; color: red;">Failed to generate preview.</div>');
    } finally {
      setLoading(false);
    }
  };

  const handleDownload = () => {
    if (!htmlContent) return;
    const element = document.createElement('div');
    element.innerHTML = htmlContent;
    
    const opt = {
      margin:       0,
      filename:     `preview-${Date.now()}.pdf`,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl h-[90vh] flex flex-col p-0 overflow-hidden">
        <DialogHeader className="px-6 py-4 border-b shrink-0 flex flex-row items-center justify-between">
          <DialogTitle>Document Preview</DialogTitle>
        </DialogHeader>

        <div className="flex-1 bg-gray-100 overflow-hidden relative">
          {loading ? (
            <div className="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10">
              <Loader2 className="w-8 h-8 animate-spin text-[#003D82] mb-4" />
              <p className="text-gray-600 font-medium">Generating preview...</p>
            </div>
          ) : (
            <iframe 
              srcDoc={htmlContent} 
              className="w-full h-full border-0 bg-white"
              title="PDF Preview"
            />
          )}
        </div>

        <DialogFooter className="px-6 py-4 border-t shrink-0 flex items-center justify-end space-x-2">
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
          <Button onClick={handleDownload} disabled={loading} className="bg-[#003D82] text-white hover:bg-[#002a5a]">
            <Download className="w-4 h-4 mr-2" />
            Download Sample PDF
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default PDFPreviewModal;