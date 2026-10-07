import React, { useRef, useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Eraser, Save, PenTool } from 'lucide-react';
import { cn } from '@/lib/utils';
import { logError } from '@/utils/debug';

const DigitalSignaturePad = ({ onSave, onCancel, className }) => {
    const canvasRef = useRef(null);
    const containerRef = useRef(null);
    const [isDrawing, setIsDrawing] = useState(false);
    const [isEmpty, setIsEmpty] = useState(true);
    
    // Canvas context configuration
    const lineWidth = 2;
    const strokeColor = '#000000';

    // Initialize canvas context
    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        try {
            const ctx = canvas.getContext('2d');
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = lineWidth;
            ctx.strokeStyle = strokeColor;
        } catch (e) {
            logError('SignaturePad', 'Canvas init failed', e);
        }
    }, []);

    // Handle Resize
    useEffect(() => {
        const handleResize = () => {
            const canvas = canvasRef.current;
            const container = containerRef.current;
            if (canvas && container) {
                // Resize to parent width, fixed height
                const newWidth = container.clientWidth;
                const newHeight = 300; // Fixed height per requirements

                // Only resize if dimensions actually changed to prevent flickers
                if (canvas.width !== newWidth || canvas.height !== newHeight) {
                    // Save existing content
                    const tempCanvas = document.createElement('canvas');
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCanvas.width = canvas.width;
                    tempCanvas.height = canvas.height;
                    tempCtx.drawImage(canvas, 0, 0);

                    // Set new dimensions
                    canvas.width = newWidth;
                    canvas.height = newHeight;

                    // Restore content (scaled if needed, but here simple restore)
                    // We typically clear on resize for signatures to ensure quality, but preserving if possible
                    // Here we will clear to avoid stretching artifacts, user can re-sign.
                    const ctx = canvas.getContext('2d');
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.lineWidth = lineWidth;
                    ctx.strokeStyle = strokeColor;
                    
                    setIsEmpty(true); // Reset state on resize
                }
            }
        };

        handleResize(); // Initial size
        window.addEventListener('resize', handleResize);
        
        // Small delay to ensure modal animation finishes and container has width
        const timeout = setTimeout(handleResize, 100);

        return () => {
            window.removeEventListener('resize', handleResize);
            clearTimeout(timeout);
        };
    }, []);

    const getCoordinates = (event) => {
        if (!canvasRef.current) return { x: 0, y: 0 };
        const canvas = canvasRef.current;
        const rect = canvas.getBoundingClientRect();
        
        let clientX, clientY;
        
        if (event.touches && event.touches.length > 0) {
            clientX = event.touches[0].clientX;
            clientY = event.touches[0].clientY;
        } else {
            clientX = event.clientX;
            clientY = event.clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    };

    const startDrawing = (event) => {
        // Only prevent default if it's a touch event to stop scrolling
        if (event.type === 'touchstart') {
            event.preventDefault(); 
        }
        
        setIsDrawing(true);
        const { x, y } = getCoordinates(event);
        const ctx = canvasRef.current.getContext('2d');
        ctx.beginPath();
        ctx.moveTo(x, y);
    };

    const draw = (event) => {
        if (!isDrawing) return;
        if (event.type === 'touchmove') {
            event.preventDefault();
        }

        const { x, y } = getCoordinates(event);
        const ctx = canvasRef.current.getContext('2d');
        ctx.lineTo(x, y);
        ctx.stroke();
        setIsEmpty(false);
    };

    const stopDrawing = () => {
        if (isDrawing) {
            const ctx = canvasRef.current.getContext('2d');
            ctx.closePath();
            setIsDrawing(false);
        }
    };

    const clearSignature = () => {
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        setIsEmpty(true);
    };

    const handleSave = () => {
        if (isEmpty) return;
        try {
            const canvas = canvasRef.current;
            const dataUrl = canvas.toDataURL('image/png');
            onSave(dataUrl);
        } catch (e) {
            console.error('Signature capture failed', e);
            logError('SignaturePad', 'Save failed', e);
        }
    };

    return (
        <div className={cn("flex flex-col gap-4 w-full", className)} ref={containerRef}>
            <div className="rounded-xl bg-white overflow-hidden relative select-none touch-none h-[300px] shadow-inner border border-gray-200">
                <canvas
                    ref={canvasRef}
                    onMouseDown={startDrawing}
                    onMouseMove={draw}
                    onMouseUp={stopDrawing}
                    onMouseLeave={stopDrawing}
                    onTouchStart={startDrawing}
                    onTouchMove={draw}
                    onTouchEnd={stopDrawing}
                    className="w-full h-full cursor-crosshair block touch-none"
                    style={{ touchAction: 'none' }} 
                />
                {isEmpty && (
                    <div className="absolute inset-0 flex items-center justify-center pointer-events-none opacity-30">
                        <div className="text-center">
                            <PenTool className="w-10 h-10 mx-auto mb-2 text-gray-400" />
                            <p className="text-gray-400 font-medium">Sign here</p>
                        </div>
                    </div>
                )}
            </div>
            
            <div className="flex justify-between items-center pt-2">
                <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={clearSignature}
                    disabled={isEmpty}
                    className="text-white hover:text-white hover:bg-red-600/20 border-white/20 bg-transparent"
                >
                    <Eraser className="w-4 h-4 mr-2" /> Clear
                </Button>
                
                <div className="flex gap-3">
                    {onCancel && (
                        <Button variant="ghost" onClick={onCancel} className="text-gray-300 hover:text-white hover:bg-white/10">
                            Cancel
                        </Button>
                    )}
                    <Button 
                        onClick={handleSave} 
                        disabled={isEmpty}
                        className="bg-[#D4AF37] hover:bg-[#b5952f] text-[#003D82] font-bold shadow-lg"
                    >
                        <Save className="w-4 h-4 mr-2" /> Save Signature
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default DigitalSignaturePad;