import React, { useState, useRef } from 'react';
import jsQR from 'jsqr';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Camera, Upload, QrCode, User, Mail, FileText, CheckCircle, ExternalLink, XCircle } from 'lucide-react';
import { getApplicationByReference } from '@/services/applicationsService';

const QRScannerPage = () => {
    const [scannedData, setScannedData] = useState(null);
    const [applicationDetails, setApplicationDetails] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const fileInputRef = useRef(null);
    const { toast } = useToast();

    const fetchApplicationData = async (reference) => {
        setLoading(true);
        setError('');
        try {
            const data = await getApplicationByReference(reference);
            if (data) {
                setApplicationDetails(data);
                toast({ title: "Application Found", description: `Loaded details for ${data.candidate_name}` });
            } else {
                setError("Application not found in database.");
            }
        } catch (err) {
            console.error(err);
            setError("Error fetching application details.");
        } finally {
            setLoading(false);
        }
    };

    const handleScan = (data) => {
        if (!data) return;
        
        try {
            // Attempt to parse JSON
            const parsed = JSON.parse(data);
            setScannedData(parsed);
            
            // Check if it's our application format
            if (parsed.reference) {
                fetchApplicationData(parsed.reference);
            } else if (parsed.type === 'application' && parsed.id) {
                 // Fallback if we used ID
                 // fetchApplicationById(parsed.id)
                 setError("QR code format recognized but missing reference number.");
            } else {
                // Generic QR
                setApplicationDetails(null);
            }
        } catch (e) {
            // Not JSON, treat as raw text (maybe just reference number string?)
            if (data.startsWith('JOB-')) {
                 setScannedData({ reference: data });
                 fetchApplicationData(data);
            } else {
                 setScannedData({ raw: data });
                 setError("QR code does not appear to be a valid Job Application code.");
            }
        }
    };

    const handleFileUpload = (event) => {
        const file = event.target.files[0];
        if (!file) return;

        setError('');
        setScannedData(null);
        setApplicationDetails(null);

        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                context.drawImage(img, 0, 0);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    handleScan(code.data);
                } else {
                    setError('No QR code found in the image.');
                    toast({ variant: "destructive", title: "Error", description: "Could not detect a QR code." });
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    };

    const InfoRow = ({ icon: Icon, label, value }) => (
        <div className="flex items-start gap-3 py-3 border-b border-gray-100 last:border-0">
            <div className="bg-blue-50 p-2 rounded-full text-blue-600 mt-0.5">
                <Icon className="w-4 h-4" />
            </div>
            <div className="flex-1">
                <p className="text-xs text-gray-500 font-medium uppercase tracking-wide">{label}</p>
                <p className="text-sm font-semibold text-gray-900 break-words">{value || 'N/A'}</p>
            </div>
        </div>
    );

    return (
        <div className="container mx-auto px-4 py-8 max-w-lg min-h-screen bg-gray-50">
            <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-[#003D82] mb-2">QR Code Scanner</h1>
                <p className="text-gray-500">Scan application confirmation codes</p>
            </div>
            
            <Card className="mb-8 border-t-4 border-t-[#D4AF37] shadow-lg">
                <CardContent className="pt-6 space-y-4">
                    <div className="flex flex-col gap-4">
                        <Button 
                            className="w-full h-16 text-lg bg-[#003D82] hover:bg-[#002d62] flex items-center justify-center gap-3 shadow-md transition-transform active:scale-95"
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <Camera className="w-6 h-6" /> Scan with Camera
                        </Button>
                        
                        <div className="relative my-2">
                            <div className="absolute inset-0 flex items-center">
                                <span className="w-full border-t border-gray-300" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-white px-2 text-gray-500">Or upload image</span>
                            </div>
                        </div>

                        <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                <Upload className="w-8 h-8 mb-3 text-gray-400" />
                                <p className="text-sm text-gray-500"><span className="font-semibold">Click to upload</span></p>
                            </div>
                            <Input 
                                type="file" 
                                accept="image/*" 
                                capture="environment"
                                className="hidden" 
                                onChange={handleFileUpload}
                                ref={fileInputRef}
                            />
                        </label>
                    </div>

                    {error && (
                        <div className="p-4 text-sm text-red-800 rounded-lg bg-red-50 text-center border border-red-100 flex items-center justify-center gap-2">
                            <XCircle className="w-4 h-4" /> {error}
                        </div>
                    )}
                </CardContent>
            </Card>

            {loading && (
                <div className="flex justify-center p-8">
                    <Loader2 className="w-8 h-8 animate-spin text-[#003D82]" />
                </div>
            )}

            {applicationDetails && (
                <Card className="animate-in fade-in slide-in-from-bottom-4 shadow-xl border border-gray-200">
                    <CardHeader className="bg-[#003D82] text-white py-4 rounded-t-lg">
                        <div className="flex justify-between items-center">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <CheckCircle className="w-5 h-5 text-[#D4AF37]" /> Verified Application
                            </CardTitle>
                            <Badge className="bg-[#D4AF37] text-[#003D82] hover:bg-[#b5952f] px-3">
                                {applicationDetails.status.toUpperCase()}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="space-y-1">
                            <InfoRow icon={QrCode} label="Reference Number" value={applicationDetails.reference_number} />
                            <InfoRow icon={User} label="Candidate Name" value={applicationDetails.candidate_name} />
                            <InfoRow icon={Mail} label="Email Address" value={applicationDetails.email} />
                            <InfoRow icon={FileText} label="Job Position" value={applicationDetails.jobs?.title} />
                            
                            {applicationDetails.cv_url && (
                                <div className="mt-6 pt-4 border-t text-center">
                                    <a 
                                        href={applicationDetails.cv_url} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center text-[#003D82] font-semibold hover:underline"
                                    >
                                        View CV Document <ExternalLink className="w-4 h-4 ml-1" />
                                    </a>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            )}
            
            {scannedData && !applicationDetails && !loading && !error && (
                 <Card className="mt-4 border-l-4 border-gray-400">
                    <CardContent className="pt-6">
                        <p className="text-gray-500 text-sm mb-2">Raw Data Scanned:</p>
                        <code className="block bg-gray-100 p-3 rounded text-xs break-all">
                            {JSON.stringify(scannedData, null, 2)}
                        </code>
                    </CardContent>
                 </Card>
            )}
        </div>
    );
};

export default QRScannerPage;