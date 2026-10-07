import React, { useState } from 'react';
import { sendPasswordResetEmail } from '@/services/adminAccountService';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2, AlertCircle, X, Mail, CheckCircle, ArrowLeft } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

const PasswordResetModal = ({ isOpen, onClose, onBackToLogin }) => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email || !email.includes('@')) {
        setError("Please enter a valid email address.");
        return;
    }

    setLoading(true);
    setError('');

    try {
      await sendPasswordResetEmail(email);
      setSuccess(true);
    } catch (err) {
      console.error(err);
      setError(err.message || 'Failed to send reset email. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <motion.div 
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={onClose}
          className="absolute inset-0 bg-blue-950/80 backdrop-blur-md"
        />

        <motion.div 
          initial={{ scale: 0.95, opacity: 0, y: 20 }}
          animate={{ scale: 1, opacity: 1, y: 0 }}
          exit={{ scale: 0.95, opacity: 0, y: 20 }}
          className="relative w-full max-w-sm bg-white rounded-xl shadow-2xl overflow-hidden"
        >
          <div className="p-6">
            <button 
                onClick={onClose}
                className="absolute top-4 right-4 p-1 hover:bg-gray-100 rounded-full text-gray-400 hover:text-gray-600"
            >
                <X className="w-5 h-5" />
            </button>

            <div className="text-center mb-6">
                <div className="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Mail className="w-6 h-6 text-[#1E3A8A]" />
                </div>
                <h2 className="text-xl font-bold text-[#1E3A8A]">Reset Password</h2>
                <p className="text-gray-500 text-sm mt-1">Enter your email to receive reset instructions</p>
            </div>

            {success ? (
                <div className="text-center space-y-4 py-4 animate-in fade-in slide-in-from-bottom-2">
                    <div className="bg-green-50 text-green-700 p-4 rounded-lg flex flex-col items-center gap-2">
                        <CheckCircle className="w-8 h-8" />
                        <p className="font-medium">Check your email</p>
                    </div>
                    <p className="text-sm text-gray-600">
                        We've sent a password reset link to <strong>{email}</strong>.
                    </p>
                    <Button onClick={onClose} className="w-full bg-[#1E3A8A] text-white">
                        Done
                    </Button>
                </div>
            ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                    {error && (
                        <div className="bg-red-50 text-red-600 p-3 rounded text-sm flex items-start gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" /> 
                            <span>{error}</span>
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="reset-email">Email Address</Label>
                        <Input 
                            id="reset-email" 
                            type="email" 
                            placeholder="you@company.com" 
                            value={email} 
                            onChange={(e) => setEmail(e.target.value)} 
                            className="focus:border-[#1E3A8A]"
                            disabled={loading}
                        />
                    </div>

                    <Button type="submit" className="w-full bg-[#1E3A8A] hover:bg-[#152861] text-white" disabled={loading}>
                        {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                        {loading ? "Sending..." : "Send Reset Link"}
                    </Button>
                </form>
            )}

            <div className="mt-6 pt-4 border-t border-gray-100 text-center">
                <button 
                    onClick={onBackToLogin}
                    className="text-gray-500 text-sm hover:text-[#1E3A8A] flex items-center justify-center gap-1 transition-colors mx-auto"
                >
                    <ArrowLeft className="w-3 h-3" /> Back to Login
                </button>
            </div>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

export default PasswordResetModal;