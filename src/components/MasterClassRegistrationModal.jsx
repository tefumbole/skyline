import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, CheckCircle } from 'lucide-react';
import MasterClassRegistrationForm from './MasterClassRegistrationForm';
import PaymentMethodSelector from './PaymentMethodSelector';
import StripePaymentForm from './StripePaymentForm';
import MobileMoneyPayment from './MobileMoneyPayment';
import { saveMasterClassRegistration, updatePaymentStatus } from '@/services/masterClassService';
import { useToast } from '@/components/ui/use-toast';
import { Button } from '@/components/ui/button';

function MasterClassRegistrationModal({ isOpen, onClose }) {
  const [step, setStep] = useState('form'); // form, method, payment, success
  const [registrationData, setRegistrationData] = useState(null);
  const [registrationId, setRegistrationId] = useState(null);
  const [selectedMethod, setSelectedMethod] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const { toast } = useToast();

  const handleFormSubmit = async (data) => {
    setIsProcessing(true);
    try {
        // Save initial registration
        const { data: savedData, error } = await saveMasterClassRegistration({
            ...data,
            payment_status: 'pending',
            amount: 300,
            currency: 'USD'
        });

        if (error) throw error;

        // If using Supabase, savedData might be an array depending on return
        const regId = Array.isArray(savedData) ? savedData[0].id : savedData.id;
        
        setRegistrationData(data);
        setRegistrationId(regId);
        setStep('method');
    } catch (err) {
        console.error(err);
        toast({
            title: "Error",
            description: "Could not save registration details. Please try again.",
            variant: "destructive"
        });
    } finally {
        setIsProcessing(false);
    }
  };

  const handleMethodSelect = (method) => {
    setSelectedMethod(method);
    setStep('payment');
  };

  const handlePaymentSuccess = async (transactionId) => {
    try {
        await updatePaymentStatus(registrationId, 'paid', transactionId);
        setStep('success');
    } catch (err) {
        console.error(err);
        // Even if update fails, payment succeeded, so we show success but log error
        setStep('success'); 
    }
  };

  const resetModal = () => {
    setStep('form');
    setRegistrationData(null);
    setRegistrationId(null);
    setSelectedMethod(null);
    onClose();
  };

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={resetModal}
            className="absolute inset-0 bg-black/70 backdrop-blur-sm"
          />

          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: 20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: 20 }}
            className="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden z-10 max-h-[90vh] flex flex-col"
          >
            {/* Header */}
            <div className="bg-navy p-6 flex items-center justify-between flex-shrink-0">
              <div>
                <h2 className="text-xl font-bold text-white">
                    {step === 'success' ? 'Registration Complete' : 'Master Class Registration'}
                </h2>
                <p className="text-blue-200 text-sm">
                    {step === 'form' && 'Enter your details'}
                    {step === 'method' && 'Select Payment Method'}
                    {step === 'payment' && 'Secure Payment'}
                    {step === 'success' && 'See you in July!'}
                </p>
              </div>
              <button 
                onClick={resetModal}
                className="text-white/70 hover:text-white hover:bg-white/10 rounded-full p-2 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Body - Scrollable */}
            <div className="p-6 bg-white overflow-y-auto flex-grow">
              
              {step === 'form' && (
                <MasterClassRegistrationForm 
                    onSubmit={handleFormSubmit} 
                    isSubmitting={isProcessing} 
                />
              )}

              {step === 'method' && (
                <PaymentMethodSelector 
                    amount={300} 
                    onSelectMethod={handleMethodSelect} 
                />
              )}

              {step === 'payment' && selectedMethod === 'card' && (
                <StripePaymentForm 
                    amount={300} 
                    onSuccess={handlePaymentSuccess}
                    onCancel={() => setStep('method')}
                />
              )}

              {step === 'payment' && selectedMethod === 'mobile_money' && (
                <MobileMoneyPayment 
                    amount={300}
                    onSuccess={handlePaymentSuccess}
                    onCancel={() => setStep('method')}
                />
              )}

              {step === 'success' && (
                <div className="text-center py-8 animate-in zoom-in-50 duration-500">
                    <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <CheckCircle className="w-10 h-10 text-green-600" />
                    </div>
                    <h3 className="text-2xl font-bold text-navy mb-2">Thank You!</h3>
                    <p className="text-gray-600 mb-6">
                        Your registration for <strong>{registrationData?.module}</strong> is confirmed.
                        We've sent a confirmation email to {registrationData?.email}.
                    </p>
                    <Button onClick={resetModal} className="btn-gold w-full py-6">
                        Close
                    </Button>
                </div>
              )}

            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

export default MasterClassRegistrationModal;