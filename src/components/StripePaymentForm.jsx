import React, { useState } from 'react';
import { loadStripe } from '@stripe/stripe-js';
import {
  Elements,
  CardElement,
  useStripe,
  useElements,
} from '@stripe/react-stripe-js';
import { Button } from '@/components/ui/button';
import { Loader2, Lock, AlertCircle } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

// Initialize Stripe with the live public key from env
const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_PUBLIC_KEY);

const CheckoutForm = ({ amount, onSuccess, onCancel }) => {
  const stripe = useStripe();
  const elements = useElements();
  const [loading, setLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const { toast } = useToast();

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    setLoading(true);
    setErrorMessage(null);

    try {
      // 1. Create a PaymentIntent on the backend (Supabase Edge Function)
      // Note: We cannot create the intent on the client side securely because it requires the Secret Key.
      const response = await fetch(`${import.meta.env.VITE_SUPABASE_URL}/functions/v1/create-payment-intent`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${import.meta.env.VITE_SUPABASE_ANON_KEY}`
        },
        body: JSON.stringify({ 
            amount: amount * 100, // Stripe expects amounts in cents
            currency: 'usd' 
        }), 
      });

      if (!response.ok) {
        throw new Error('Network response was not ok. Please contact support if this persists.');
      }

      const { clientSecret } = await response.json();

      if (!clientSecret) {
          throw new Error("Failed to initialize payment session.");
      }

      // 2. Confirm the card payment on the client side
      const result = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
          card: elements.getElement(CardElement),
        },
      });

      if (result.error) {
        // Show error to your customer (e.g., insufficient funds)
        console.error(result.error.message);
        setErrorMessage(result.error.message);
        toast({
            title: "Payment Failed",
            description: result.error.message,
            variant: "destructive"
        });
      } else {
        // The payment has been processed!
        if (result.paymentIntent.status === 'succeeded') {
          console.log('Payment succeeded:', result.paymentIntent.id);
          onSuccess(result.paymentIntent.id);
        }
      }

    } catch (err) {
      console.error("Payment Error:", err);
      // Fallback for demo purposes if backend function doesn't exist yet
      if (err.message.includes('Network response') || err.message.includes('Failed to fetch')) {
         setErrorMessage("Backend payment service is unreachable. (Note: Ensure Supabase Edge Function 'create-payment-intent' is deployed).");
      } else {
         setErrorMessage(err.message || "An unexpected error occurred.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="bg-white p-4 rounded-md border border-gray-200 shadow-sm">
        <CardElement
          options={{
            style: {
              base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                  color: '#aab7c4',
                },
              },
              invalid: {
                color: '#9e2146',
              },
            },
            hidePostalCode: true, // Simplified for this view, consider enabling for better fraud protection
          }}
        />
      </div>

      {errorMessage && (
        <div className="flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-md text-sm">
            <AlertCircle className="w-4 h-4" />
            <span>{errorMessage}</span>
        </div>
      )}
      
      <div className="flex gap-3">
        <Button 
            type="button" 
            variant="outline" 
            onClick={onCancel}
            className="flex-1"
        >
            Cancel
        </Button>
        <Button 
            type="submit" 
            disabled={!stripe || loading} 
            className="flex-1 btn-blue"
        >
            {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Lock className="w-4 h-4 mr-2" />}
            Pay ${amount}
        </Button>
      </div>
      
      <div className="flex justify-center items-center gap-2 mt-2">
         <Lock className="w-3 h-3 text-gray-400" />
         <span className="text-xs text-gray-400">Payments secured by Stripe</span>
      </div>
    </form>
  );
};

function StripePaymentForm({ amount, onSuccess, onCancel }) {
  if (!import.meta.env.VITE_STRIPE_PUBLIC_KEY) {
      return (
          <div className="text-red-500 bg-red-50 p-4 rounded-lg">
              Error: VITE_STRIPE_PUBLIC_KEY is missing in environment variables.
          </div>
      );
  }

  return (
    <div className="w-full">
      <h3 className="text-xl font-bold text-navy mb-4">Pay with Card</h3>
      <Elements stripe={stripePromise}>
        <CheckoutForm amount={amount} onSuccess={onSuccess} onCancel={onCancel} />
      </Elements>
    </div>
  );
}

export default StripePaymentForm;