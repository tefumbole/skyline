import React, { useState, useEffect } from 'react';
import { useParams, Link, useLocation } from 'react-router-dom';
import { getApplicationByReference } from '@/services/applicationsService';
import { generateQRCode } from '@/services/qrCodeService';
import { generateApplicationPDF } from '@/services/applicationPdfService';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Download, Home, Printer, Loader2, AlertCircle, Key, UserCheck } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

const ApplicationConfirmationPage = () => {
  const { referenceNumber } = useParams();
  const location = useLocation();
  const createdAccount = location.state?.createdAccount; // Passed from form submission

  const [application, setApplication] = useState(null);
  const [qrCodeUrl, setQrCodeUrl] = useState(null);
  const [loading, setLoading] = useState(true);
  const [pdfUrl, setPdfUrl] = useState(null);
  const { toast } = useToast();

  useEffect(() => {
    const init = async () => {
      try {
        if (!referenceNumber) throw new Error("No reference number provided");
        
        const appData = await getApplicationByReference(referenceNumber);
        if (!appData) throw new Error("Application not found");
        setApplication(appData);

        const qrUrl = await generateQRCode(referenceNumber);
        setQrCodeUrl(qrUrl);

        if (appData && appData.jobs) {
             const doc = generateApplicationPDF(appData, appData.jobs, qrUrl);
             const pdfBlob = doc.output('blob');
             const url = URL.createObjectURL(pdfBlob);
             setPdfUrl(url);
        }

      } catch (error) {
        console.error(error);
        toast({ title: "Error", description: "Failed to load confirmation details.", variant: "destructive" });
      } finally {
        setLoading(false);
      }
    };

    init();
    
    return () => {
        if (pdfUrl) URL.revokeObjectURL(pdfUrl);
    };
  }, [referenceNumber]);

  const handleDownloadPDF = () => {
     if (application && qrCodeUrl) {
         const doc = generateApplicationPDF(application, application.jobs, qrCodeUrl);
         doc.save(`Application_${application.reference_number}.pdf`);
     }
  };

  if (loading) return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
          <div className="text-center">
              <Loader2 className="w-12 h-12 animate-spin text-[#003D82] mx-auto mb-4" />
              <h2 className="text-xl font-semibold text-gray-700">Generating Confirmation...</h2>
          </div>
      </div>
  );

  if (!application) return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
          <Card className="max-w-md w-full text-center p-8">
              <AlertCircle className="w-16 h-16 text-red-500 mx-auto mb-4" />
              <h2 className="text-2xl font-bold text-gray-800 mb-2">Application Not Found</h2>
              <Link to="/apply-now"><Button>Browse Jobs</Button></Link>
          </Card>
      </div>
  );

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4">
      <div className="max-w-4xl mx-auto space-y-8">
        {/* Account Creation Success Banner */}
        {createdAccount && (
            <div className="bg-green-50 rounded-xl shadow border border-green-200 p-6 flex flex-col md:flex-row items-center gap-6 animate-in slide-in-from-top duration-500">
                <div className="bg-green-100 p-4 rounded-full">
                    <UserCheck className="w-10 h-10 text-green-600" />
                </div>
                <div className="flex-1 text-center md:text-left space-y-2">
                    <h2 className="text-xl font-bold text-green-800">Account Created Successfully</h2>
                    <p className="text-green-700">You can now login to track your application status.</p>
                    <div className="bg-white/50 p-3 rounded border border-green-200 text-sm font-mono text-green-900 inline-block text-left">
                        <div><span className="font-semibold">Email:</span> {createdAccount.email}</div>
                        <div><span className="font-semibold">Password:</span> {createdAccount.password.slice(0, 3)}•••••••••</div>
                    </div>
                    <div className="text-xs text-green-600 mt-1 flex items-center gap-1">
                        <Key className="w-3 h-3" /> Credentials sent to your email
                    </div>
                </div>
                <div>
                    <Link to="/applicant-login">
                        <Button className="bg-green-600 hover:bg-green-700 text-white">
                            Login Now
                        </Button>
                    </Link>
                </div>
            </div>
        )}

        {/* Success Banner */}
        <div className="bg-white rounded-xl shadow-lg p-8 text-center border-t-8 border-[#003D82]">
            <CheckCircle className="w-20 h-20 text-[#003D82] mx-auto mb-4" />
            <h1 className="text-3xl font-bold text-[#003D82] mb-2">Application Submitted Successfully!</h1>
            <p className="text-gray-600 text-lg">
                We have received your application for <span className="font-semibold text-[#003D82]">{application.jobs?.title}</span>.
            </p>
            <div className="mt-6 inline-block bg-gray-100 px-6 py-3 rounded-lg border border-gray-200">
                <span className="text-gray-500 text-sm block mb-1">YOUR REFERENCE NUMBER</span>
                <span className="text-2xl font-mono font-bold text-[#D4AF37] tracking-wider">{application.reference_number}</span>
            </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Left: Actions & QR */}
            <Card>
                <CardHeader>
                    <CardTitle>Next Steps</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                    <p className="text-gray-600">
                        Please save your reference number. You can use the QR code below to quickly access your application status.
                    </p>
                    
                    <div className="flex justify-center p-4 bg-white border rounded-lg shadow-inner">
                        {qrCodeUrl && <img src={qrCodeUrl} alt="Application QR" className="w-48 h-48" />}
                    </div>
                    
                    <div className="space-y-3">
                        <Button onClick={handleDownloadPDF} className="w-full bg-[#003D82] hover:bg-[#002d62]">
                            <Download className="w-4 h-4 mr-2" /> Download Confirmation PDF
                        </Button>
                        <Link to="/">
                            <Button variant="outline" className="w-full">
                                <Home className="w-4 h-4 mr-2" /> Return to Home
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            {/* Right: PDF Preview */}
            <Card className="overflow-hidden flex flex-col h-[500px]">
                <CardHeader className="bg-gray-50 py-3 border-b">
                    <CardTitle className="text-sm flex items-center gap-2 text-gray-500">
                        <Printer className="w-4 h-4" /> Document Preview
                    </CardTitle>
                </CardHeader>
                <div className="flex-1 bg-gray-200 relative">
                    {pdfUrl ? (
                        <iframe src={pdfUrl} className="w-full h-full border-none" title="PDF Preview" />
                    ) : (
                        <div className="flex items-center justify-center h-full text-gray-500">
                            Loading Preview...
                        </div>
                    )}
                </div>
            </Card>
        </div>
      </div>
    </div>
  );
};

export default ApplicationConfirmationPage;