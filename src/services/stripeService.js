import { loadStripe } from '@stripe/stripe-js';
import { supabase } from '@/lib/customSupabaseClient';

// Initialize Stripe with Public Key
// Note: In a real app, use import.meta.env.VITE_STRIPE_PUBLIC_KEY
const STRIPE_PUBLIC_KEY = 'pk_test_51O...'; // Placeholder - User should replace with real key
let stripePromise;

export const initializeStripe = () => {
  if (!stripePromise) {
    // In a real scenario, this key should come from environment variables
    // For now, we return null if key is not configured, handling it gracefully in UI
    const key = import.meta.env.VITE_STRIPE_PUBLIC_KEY || 'pk_test_placeholder';
    stripePromise = loadStripe(key);
  }
  return stripePromise;
};

/**
 * Creates a Payment Intent via Supabase Edge Function to ensure secret key safety
 */
export const createPaymentIntent = async (amount, currency = 'usd', metadata = {}) => {
  try {
    const { data, error } = await supabase.functions.invoke('create-payment-intent', {
      body: { amount, currency, metadata }
    });

    if (error) throw error;
    if (data.error) throw new Error(data.error);
    
    return data; // Returns { clientSecret: '...' }
  } catch (error) {
    console.error("Error creating payment intent:", error);
    throw error;
  }
};

export const confirmPayment = async (stripe, elements) => {
  if (!stripe || !elements) throw new Error("Stripe not initialized");

  const { error, paymentIntent } = await stripe.confirmPayment({
    elements,
    confirmParams: {
      return_url: window.location.origin + '/registration/success', // Placeholder
    },
    redirect: 'if_required' // Avoid redirect if possible
  });

  if (error) throw error;
  return paymentIntent;
};