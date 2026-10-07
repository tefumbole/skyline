import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2, Smartphone, ArrowLeft, AlertCircle } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

function MobileMoneyPayment({ amount, onSuccess, onCancel }) {
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [pollInterval, setPollInterval] = useState(null);
  const { toast } = useToast();

  // Cleanup polling on unmount
  useEffect(() => {
    return () => {
      if (pollInterval) clearInterval(pollInterval);
    };
  }, [pollInterval]);

  const validatePhone = (phoneNumber) => {
    // Regex for Rwanda Phone numbers (e.g., 078..., 079..., +2507...)
    // This allows formats like 0788123456 or 250788123456
    const rwandaPhoneRegex = /^(?:\+250|0|250)(7\d{8})$/;
    return rwandaPhoneRegex.test(phoneNumber);
  };

  const checkTransactionStatus = async (refId) => {
    try {
        // Call backend to check status
        const response = await fetch(`${import.meta.env.VITE_SUPABASE_URL}/functions/v1/mobile-money-status?ref=${refId}`, {
            headers: {
               'Authorization': `Bearer ${import.meta.env.VITE_SUPABASE_ANON_KEY}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.status === 'successful') {
                clearInterval(pollInterval);
                setLoading(false);
                onSuccess(data.transaction_id || refId);
                toast({ title: "Payment Successful", description: "Your Mobile Money transaction was confirmed." });
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                setLoading(false);
                setError("Transaction failed or was declined by user.");
            }
        }
    } catch (err) {
        console.error("Polling error", err);
    }
  };

  const handlePayment = async (e) => {
    e.preventDefault();
    setError(null);

    // 1. Validation
    if (!phone) {
        setError("Please enter a phone number.");
        return;
    }
    
    // Normalize phone number to include country code if missing, or validate format
    if (!validatePhone(phone)) {
        setError("Invalid phone number format. Use Rwanda format (e.g., 0788123456).");
        return;
    }

    setLoading(true);

    try {
        // 2. Initiate Payment via Backend (Supabase Edge Function)
        // We cannot call MTN/Airtel APIs directly from frontend due to CORS and Security (API Keys)
        const response = await fetch(`${import.meta.env.VITE_SUPABASE_URL}/functions/v1/mobile-money-payment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${import.meta.env.VITE_SUPABASE_ANON_KEY}`
            },
            body: JSON.stringify({ 
                phone: phone, 
                amount: amount,
                currency: 'RWF', // Assuming conversion or USD support depending on gateway
                provider: 'mtn' // or dynamic based on phone prefix
            })
        });

        if (!response.ok) {
            // For demo robustness, if backend is missing, throw specific error
            if (response.status === 404) {
                 throw new Error("Payment service endpoint not found. Please deploy 'mobile-money-payment' Edge Function.");
            }
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || "Failed to initiate payment.");
        }

        const data = await response.json();
        
        // 3. Start Polling for completion
        // Real mobile money flows are async. We wait for user to confirm on their phone.
        toast({
            title: "Check your phone",
            description: "A payment prompt has been sent to your device. Please approve it.",
            duration: 10000,
        });

        // Start polling status every 3 seconds
        const intervalId = setInterval(() => {
            checkTransactionStatus(data.reference_id);
        }, 3000);
        setPollInterval(intervalId);

        // Fallback timeout after 2 minutes
        setTimeout(() => {
            if (intervalId) clearInterval(intervalId);
            if (loading) {
                setLoading(false);
                setError("Transaction timed out. Please try again.");
            }
        }, 120000);

    } catch (err) {
        setLoading(false);
        console.error("Momo Error:", err);
        setError(err.message || "An unexpected error occurred.");
    }
  };

  return (
    <div className="space-y-6">
      <div className="text-center mb-6">
         <h3 className="text-xl font-bold text-navy">Mobile Money Payment</h3>
         <p className="text-sm text-gray-500">MTN Mobile Money / Airtel Money</p>
      </div>

      <form onSubmit={handlePayment} className="space-y-6">
        <div className="space-y-2">
            <Label htmlFor="momo-phone">Phone Number (Rwanda)</Label>
            <div className="relative">
                <Smartphone className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <Input 
                    id="momo-phone"
                    placeholder="078 XXX XXXX" 
                    className="pl-10"
                    value={phone}
                    onChange={(e) => {
                        setPhone(e.target.value);
                        setError(null);
                    }}
                    disabled={loading}
                />
            </div>
            <p className="text-xs text-gray-500">
                You will receive a prompt on this phone to approve the payment.
            </p>
        </div>

        {error && (
            <div className="flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-md text-sm animate-in fade-in">
                <AlertCircle className="w-4 h-4" />
                <span>{error}</span>
            </div>
        )}

        <div className="flex gap-3 pt-2">
            <Button 
                type="button" 
                variant="outline" 
                onClick={onCancel}
                className="flex-1"
                disabled={loading}
            >
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back
            </Button>
            <Button 
                type="submit" 
                disabled={loading} 
                className="flex-1 bg-yellow-500 hover:bg-yellow-600 text-black font-semibold"
            >
                {loading ? (
                    <>
                        <Loader2 className="w-4 h-4 animate-spin mr-2" />
                        Processing...
                    </>
                ) : (
                    `Pay ${amount} USD`
                )}
            </Button>
        </div>
      </form>
    </div>
  );
}

export default MobileMoneyPayment;