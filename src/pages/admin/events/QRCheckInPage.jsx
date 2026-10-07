import React, { useState, useEffect, useRef } from 'react';
import jsQR from 'jsqr';
import AdminHorizontalNav from '@/components/admin/AdminHorizontalNav';
import { INVITATION_NAV } from '@/config/invitationNavConfig';
import { checkInInvitation } from '@/services/invitationService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { Camera, CheckCircle, XCircle, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';

const QRCheckInPage = () => {
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const [scanning, setScanning] = useState(false);
    const [scannedLogs, setScannedLogs] = useState([]);
    const { toast } = useToast();

    const startScanner = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                videoRef.current.setAttribute("playsinline", true);
                videoRef.current.play();
                setScanning(true);
                requestAnimationFrame(tick);
            }
        } catch (err) {
            toast({ title: "Camera Error", description: "Please allow camera access.", variant: "destructive" });
        }
    };

    const stopScanner = () => {
        if (videoRef.current && videoRef.current.srcObject) {
            videoRef.current.srcObject.getTracks().forEach(track => track.stop());
            setScanning(false);
        }
    };

    useEffect(() => {
        return () => stopScanner();
    }, []);

    const tick = () => {
        if (!scanning) return;
        if (videoRef.current && videoRef.current.readyState === videoRef.current.HAVE_ENOUGH_DATA) {
            const canvas = canvasRef.current;
            const video = videoRef.current;
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert",
            });

            if (code) {
                handleScan(code.data);
                // Pause scanning briefly to prevent rapid duplicates
                setScanning(false);
                setTimeout(() => {
                    if (videoRef.current) {
                        setScanning(true);
                        requestAnimationFrame(tick);
                    }
                }, 3000);
                return; // Stop current tick loop
            }
        }
        if (scanning) requestAnimationFrame(tick);
    };

    const handleScan = async (qrCodeData) => {
        if (!qrCodeData) return;
        
        try {
            const res = await checkInInvitation(qrCodeData);
            const logEntry = {
                id: Date.now(),
                code: qrCodeData,
                time: new Date().toLocaleTimeString(),
                success: res.success,
                guest: res.success ? res.data.guest_name : 'Unknown',
                category: res.success ? res.data.category : 'N/A',
                message: res.success ? 'Checked In' : res.error
            };

            setScannedLogs(prev => [logEntry, ...prev].slice(0, 10)); // Keep last 10
            
            if (res.success) {
                toast({ title: "Success!", description: `${res.data.guest_name} checked in successfully.`, className: "bg-green-600 text-white" });
            } else {
                toast({ title: "Scan Failed", description: res.error, variant: "destructive" });
            }
        } catch (error) {
            toast({ title: "Error", description: "System error during check-in.", variant: "destructive" });
        }
    };

    return (
        <div className="max-w-5xl mx-auto p-6 space-y-6">
            <AdminHorizontalNav
                items={INVITATION_NAV}
                title="Digital Invitations"
                description="Scan guest invitation QR codes to manage event entry."
            />

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {/* Scanner Section */}
                <Card className="overflow-hidden shadow-lg border-2 border-dashed border-gray-300">
                    <CardHeader className="bg-gray-50 border-b pb-4">
                        <CardTitle className="flex justify-between items-center text-lg">
                            <span className="flex items-center"><Camera className="w-5 h-5 mr-2 text-blue-600" /> Live Scanner</span>
                            {scanning ? (
                                <Badge className="bg-green-500 animate-pulse">Scanning Active</Badge>
                            ) : (
                                <Badge variant="outline" className="text-gray-500">Camera Off</Badge>
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 relative bg-black aspect-video flex items-center justify-center">
                        {!scanning && (
                            <div className="absolute z-10 flex flex-col items-center">
                                <Button onClick={startScanner} className="bg-[#003D82] text-white px-8 py-4 rounded-full text-lg shadow-xl hover:scale-105 transition-transform">
                                    Start Camera
                                </Button>
                            </div>
                        )}
                        <video ref={videoRef} className={`w-full h-full object-cover ${scanning ? 'opacity-100' : 'opacity-30'}`}></video>
                        <canvas ref={canvasRef} style={{ display: 'none' }}></canvas>
                        
                        {/* Scanner overlay box */}
                        {scanning && (
                            <div className="absolute inset-0 pointer-events-none flex items-center justify-center">
                                <div className="w-64 h-64 border-4 border-blue-500 rounded-2xl relative">
                                    <div className="absolute w-full h-0.5 bg-blue-500/50 shadow-[0_0_8px_rgba(59,130,246,0.8)] animate-scan"></div>
                                </div>
                            </div>
                        )}
                    </CardContent>
                    {scanning && (
                        <div className="p-4 bg-gray-50 flex justify-center">
                            <Button variant="destructive" onClick={stopScanner}>Stop Scanner</Button>
                        </div>
                    )}
                </Card>

                {/* Live Log Section */}
                <Card className="shadow-md h-fit">
                    <CardHeader className="border-b bg-gray-50">
                        <CardTitle className="flex items-center text-lg">
                            <Users className="w-5 h-5 mr-2 text-blue-600" /> Recent Check-ins
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="max-h-[400px] overflow-y-auto">
                            {scannedLogs.length === 0 ? (
                                <div className="p-8 text-center text-gray-400">
                                    <p>No check-ins recorded yet.</p>
                                    <p className="text-sm">Scan a QR code to see it here.</p>
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {scannedLogs.map(log => (
                                        <div key={log.id} className={`p-4 flex items-center justify-between transition-colors ${log.success ? (log.category === 'VIP' ? 'bg-yellow-50 hover:bg-yellow-100' : 'bg-white hover:bg-gray-50') : 'bg-red-50'}`}>
                                            <div className="flex items-start gap-3">
                                                {log.success ? (
                                                    <CheckCircle className={`w-6 h-6 mt-1 shrink-0 ${log.category === 'VIP' ? 'text-yellow-600' : 'text-green-500'}`} />
                                                ) : (
                                                    <XCircle className="w-6 h-6 text-red-500 mt-1 shrink-0" />
                                                )}
                                                <div>
                                                    <p className={`font-bold ${log.category === 'VIP' && log.success ? 'text-yellow-900' : 'text-gray-900'}`}>{log.guest}</p>
                                                    <div className="flex items-center gap-2 mt-1">
                                                        <span className="text-xs text-gray-500">{log.time}</span>
                                                        {log.success && (
                                                            <Badge variant="outline" className={`text-[10px] ${log.category === 'VIP' ? 'bg-yellow-200 text-yellow-800 border-yellow-400' : 'bg-blue-50 text-blue-700 border-blue-200'}`}>
                                                                {log.category}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            {!log.success && (
                                                <div className="text-xs text-red-600 font-medium max-w-[120px] text-right">
                                                    {log.message}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <style dangerouslySetInnerHTML={{ __html: `
                @keyframes scan {
                    0% { top: 0; }
                    50% { top: 100%; }
                    100% { top: 0; }
                }
                .animate-scan {
                    animation: scan 2s linear infinite;
                }
            `}} />
        </div>
    );
};

export default QRCheckInPage;