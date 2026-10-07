import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import DigitalSignaturePad from './DigitalSignaturePad';
import { Button } from '@/components/ui/button';
import { Loader2, PenTool } from 'lucide-react';

const DigitalSignatureModal = ({ open, onOpenChange, onSave, loading }) => {
    const [signatureData, setSignatureData] = useState(null);

    const handleSaveSignature = (dataUrl) => {
        setSignatureData(dataUrl);
        if (onSave) {
            onSave(dataUrl);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <AnimatePresence>
                {open && (
                    <DialogContent 
                        className="bg-[#003D82] text-white border-0 shadow-2xl p-0 max-w-2xl"
                        as={motion.div}
                        initial={{ opacity: 0, scale: 0.9, y: 50 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.9, y: 50 }}
                        transition={{ duration: 0.3 }}
                    >
                        <DialogHeader className="p-6 pb-4">
                            <DialogTitle className="flex items-center gap-2 text-xl text-[#D4AF37]">
                                <PenTool className="w-5 h-5" />
                                Provide Your Signature
                            </DialogTitle>
                            <DialogDescription className="text-blue-200">
                                Please sign in the box below to confirm your agreement.
                            </DialogDescription>
                        </DialogHeader>
                        
                        <div className="px-6 pb-6">
                           <DigitalSignaturePad onSave={handleSaveSignature} onCancel={() => onOpenChange(false)} />
                        </div>
                        
                        {loading && (
                            <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <Loader2 className="w-8 h-8 animate-spin text-[#D4AF37]" />
                            </div>
                        )}
                    </DialogContent>
                )}
            </AnimatePresence>
        </Dialog>
    );
};

export default DigitalSignatureModal;