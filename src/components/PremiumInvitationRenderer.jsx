import React, { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Download, Printer } from 'lucide-react';

export const drawPremiumInvitation = async (canvas, { event, guestName, template, qrCodeUrl, invitationType, rsvpUrl, customMessage }) => {
    if (!canvas) return null;
    const ctx = canvas.getContext('2d');
    
    // Clear canvas
    ctx.clearRect(0, 0, 1200, 800);

    // 1. Draw Background Image
    if (template?.background_image_url) {
        try {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
                img.src = template.background_image_url;
            });
            ctx.drawImage(img, 0, 0, 1200, 800);
        } catch (err) {
            console.error('Failed to load background image:', err);
            ctx.fillStyle = '#1a1a1a';
            ctx.fillRect(0, 0, 1200, 800);
        }
    } else {
        ctx.fillStyle = '#1a1a1a';
        ctx.fillRect(0, 0, 1200, 800);
    }

    // 2. Semi-transparent dark overlay for text readability
    ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
    ctx.fillRect(0, 0, 1200, 800);

    // 3. Text Configuration (Gold Theme)
    ctx.fillStyle = '#D4AF37'; // Gold
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    // 4. Event Title
    ctx.font = 'bold 48px Georgia, serif';
    const eventName = (event?.name || 'PREMIUM EVENT').toUpperCase();
    ctx.fillText(eventName, 600, 150);

    // 5. Decorative Line
    ctx.beginPath();
    ctx.moveTo(450, 200);
    ctx.lineTo(750, 200);
    ctx.strokeStyle = '#D4AF37';
    ctx.lineWidth = 2;
    ctx.stroke();

    // 6. Guest Name
    ctx.font = 'italic 36px Georgia, serif';
    ctx.fillText(`Dear ${guestName || 'Valued Guest'}`, 600, 280);

    // 7. Custom Message / Invitation Type
    ctx.font = 'normal 24px Georgia, serif';
    const message = customMessage || `You are cordially invited as a ${invitationType || 'Guest'}`;
    
    // Handle multi-line custom message
    const lines = message.split('\n');
    let msgY = 350;
    lines.forEach(line => {
        ctx.fillText(line, 600, msgY);
        msgY += 35;
    });

    // 8. Event Details
    const detailsY = Math.max(450, msgY + 40);
    ctx.font = 'normal 22px Georgia, serif';
    const dateStr = event?.event_date ? new Date(event.event_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : 'Date: TBD';
    const timeStr = event?.event_time || 'Time: TBD';
    const venueStr = event?.location || 'Venue: TBD';
    
    ctx.fillText(`${dateStr} at ${timeStr}`, 600, detailsY);
    ctx.fillText(`${venueStr}`, 600, detailsY + 40);

    // 9. RSVP Link
    if (rsvpUrl) {
        ctx.font = 'normal 18px Georgia, serif';
        ctx.fillText(`Please RSVP at:`, 600, detailsY + 120);
        ctx.font = 'italic 18px Georgia, serif';
        ctx.fillText(rsvpUrl, 600, detailsY + 150);
    }

    // 10. QR Code
    if (qrCodeUrl) {
        try {
            const qrImg = new Image();
            qrImg.crossOrigin = 'Anonymous';
            await new Promise((resolve, reject) => {
                qrImg.onload = resolve;
                qrImg.onerror = reject;
                qrImg.src = qrCodeUrl;
            });
            
            const qrSize = 150;
            const qrX = 1200 - qrSize - 40;
            const qrY = 800 - qrSize - 40;

            // Gold Border for QR
            ctx.strokeStyle = '#D4AF37';
            ctx.lineWidth = 4;
            ctx.strokeRect(qrX - 6, qrY - 6, qrSize + 12, qrSize + 12);

            // Draw QR
            ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);
        } catch (err) {
            console.error('Failed to draw QR code on canvas:', err);
        }
    }

    return canvas.toDataURL('image/png');
};

const PremiumInvitationRenderer = (props) => {
    const canvasRef = useRef(null);

    useEffect(() => {
        if (canvasRef.current) {
            drawPremiumInvitation(canvasRef.current, props);
        }
    }, [props]);

    const handleDownload = () => {
        if (!canvasRef.current) return;
        const link = document.createElement('a');
        link.download = `${props.guestName || 'Invitation'}_Ticket.png`;
        link.href = canvasRef.current.toDataURL('image/png');
        link.click();
    };

    const handlePrint = () => {
        if (!canvasRef.current) return;
        const dataUrl = canvasRef.current.toDataURL('image/png');
        const win = window.open('');
        win.document.write(`
            <html>
                <head><title>Print Invitation</title></head>
                <body style="margin:0;display:flex;justify-content:center;align-items:center;height:100vh;background:#fff;">
                    <img src="${dataUrl}" style="max-width:100%;max-height:100%;object-fit:contain;" onload="window.print();window.close()"/>
                </body>
            </html>
        `);
    };

    return (
        <div className="flex flex-col items-center w-full space-y-4">
            <div className="w-full overflow-hidden rounded-xl shadow-2xl border border-gray-200 bg-black">
                <canvas 
                    ref={canvasRef} 
                    width={1200} 
                    height={800} 
                    className="w-full h-auto object-contain block"
                    style={{ aspectRatio: '1200/800' }}
                />
            </div>
            
            <div className="flex gap-4 w-full justify-center pt-2">
                <Button variant="outline" type="button" onClick={handleDownload} className="border-[#003D82] text-[#003D82] hover:bg-blue-50">
                    <Download className="w-4 h-4 mr-2" /> Download Image
                </Button>
                <Button variant="outline" type="button" onClick={handlePrint} className="border-[#003D82] text-[#003D82] hover:bg-blue-50">
                    <Printer className="w-4 h-4 mr-2" /> Print Ticket
                </Button>
            </div>
        </div>
    );
};

export default PremiumInvitationRenderer;