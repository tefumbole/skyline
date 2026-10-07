import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getShareholderByReference } from '@/services/shareholderService';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle2, FileText, Download, ArrowRight, Loader2 } from 'lucide-react';

const ShareholderConfirmationPage = () => {
  const { referenceNumber } = useParams();
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      if (!referenceNumber) return;
      
      const { data: shareholderData, error } = await getShareholderByReference(referenceNumber);
      
      if (error || !shareholderData) {
        setError("Could not find registration details.");
      } else {
        setData(shareholderData);
      }
      setLoading(false);
    };

    fetchData();
  }, [referenceNumber]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <Loader2 className="h-12 w-12 animate-spin text-blue-600" />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-4">
        <div className="text-red-500 text-xl font-bold mb-4">Registration Not Found</div>
        <p className="text-gray-600 mb-6">We couldn't find a registration with reference: {referenceNumber}</p>
        <Button onClick={() => navigate('/')}>Return Home</Button>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto space-y-8">
        
        {/* Success Header */}
        <div className="text-center space-y-4">
          <div className="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100">
            <CheckCircle2 className="h-10 w-10 text-green-600" />
          </div>
          <h1 className="text-3xl font-extrabold text-gray-900">Registration Successful!</h1>
          <p className="text-lg text-gray-600">
            Thank you for registering as a shareholder. Your application has been received.
          </p>
        </div>

        {/* Reference Card */}
        <Card className="border-t-4 border-t-blue-600 shadow-lg">
          <CardHeader className="bg-gray-50 border-b border-gray-100">
            <CardTitle className="text-sm font-medium text-gray-500 uppercase tracking-wider text-center">
              Reference Number
            </CardTitle>
            <div className="text-3xl font-mono font-bold text-center text-blue-600 mt-2">
              {data.reference_number}
            </div>
          </CardHeader>
          <CardContent className="p-8">
            <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-8">
              <div className="sm:col-span-1">
                <dt className="text-sm font-medium text-gray-500">Full Name</dt>
                <dd className="mt-1 text-lg font-semibold text-gray-900">{data.name}</dd>
              </div>
              <div className="sm:col-span-1">
                <dt className="text-sm font-medium text-gray-500">Email Address</dt>
                <dd className="mt-1 text-lg font-semibold text-gray-900">{data.email}</dd>
              </div>
              <div className="sm:col-span-1">
                <dt className="text-sm font-medium text-gray-500">Phone Number</dt>
                <dd className="mt-1 text-lg font-semibold text-gray-900">{data.phone}</dd>
              </div>
              <div className="sm:col-span-1">
                <dt className="text-sm font-medium text-gray-500">Shares Requested</dt>
                <dd className="mt-1 text-lg font-semibold text-gray-900">{data.shares_assigned}</dd>
              </div>
              <div className="sm:col-span-2 pt-4 border-t border-gray-100">
                 <div className="flex justify-between items-center">
                    <dt className="text-base font-medium text-gray-500">Total Investment Amount</dt>
                    <dd className="text-2xl font-bold text-blue-600">
                        {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(data.total_amount)}
                    </dd>
                 </div>
              </div>
            </dl>
          </CardContent>
        </Card>

        {/* Next Steps */}
        <div className="bg-white rounded-xl shadow-md p-6 border border-gray-100">
            <h3 className="text-lg font-bold text-gray-900 mb-4">What Happens Next?</h3>
            <ul className="space-y-4">
                <li className="flex gap-3">
                    <div className="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">1</div>
                    <p className="text-gray-600">We will review your application and verify your details within 24-48 hours.</p>
                </li>
                <li className="flex gap-3">
                    <div className="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">2</div>
                    <p className="text-gray-600">You will receive an email with payment instructions and the shareholder agreement.</p>
                </li>
                <li className="flex gap-3">
                    <div className="flex-shrink-0 h-6 w-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">3</div>
                    <p className="text-gray-600">Once payment is confirmed, you will be issued a digital share certificate.</p>
                </li>
            </ul>
        </div>

        <div className="flex justify-center gap-4">
            <Button variant="outline" className="gap-2" onClick={() => window.print()}>
                <Download className="h-4 w-4" />
                Save Confirmation
            </Button>
            <Button className="gap-2" onClick={() => navigate('/')}>
                Return to Home
                <ArrowRight className="h-4 w-4" />
            </Button>
        </div>

      </div>
    </div>
  );
};

export default ShareholderConfirmationPage;