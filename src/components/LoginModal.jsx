import React, { useState, useEffect } from 'react';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2, AlertCircle, X, LogIn, KeyRound } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { useNavigate } from 'react-router-dom';

const LoginModal = ({ isOpen, onClose, onSwitchToSignUp, redirectOnSuccess }) => {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (isOpen) {
      setError('');
      setPassword('');
    }
  }, [isOpen]);

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });

      if (error) throw error;
      
      if (!data.session) {
        throw new Error("Login successful but no session was created. Please try again.");
      }

      // Session is handled by AuthContext via onAuthStateChange
      // We just need to close the modal and redirect
      
      onClose();
      if (redirectOnSuccess) {
          navigate(redirectOnSuccess);
      }
    } catch (err) {
      console.error(err);
      // Handle specific error messages for better UX
      let message = err.message || 'Invalid login credentials';
      if (message.includes('Invalid login credentials')) {
        message = 'Incorrect email or password.';
      }
      setError(message);
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
          className="absolute inset-0 bg-black/60 backdrop-blur-md"
        />

        <motion.div 
          initial={{ scale: 0.95, opacity: 0, y: 20 }}
          animate={{ scale: 1, opacity: 1, y: 0 }}
          exit={{ scale: 0.95, opacity: 0, y: 20 }}
          className="relative w-full max-w-md bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 border border-white/20"
        >
          <button 
            onClick={onClose}
            className="absolute top-4 right-4 p-2 hover:bg-gray-100 rounded-full transition-colors text-gray-500 hover:text-gray-700"
          >
            <X className="w-5 h-5" />
          </button>

          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 mb-4">
                <KeyRound className="w-6 h-6 text-[#003D82]" />
            </div>
            <h2 className="text-3xl font-bold text-[#003D82] tracking-tight">Welcome Back</h2>
            <p className="text-gray-500 mt-2">Please sign in to continue</p>
          </div>

          {error && (
            <div className="bg-red-50 text-red-600 p-4 rounded-xl text-sm flex items-start gap-3 mb-6 border border-red-100 shadow-sm">
              <AlertCircle className="w-5 h-5 shrink-0 mt-0.5" /> 
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleLogin} className="space-y-5">
            <div className="space-y-2">
              <Label htmlFor="login-email">Email Address</Label>
              <Input 
                id="login-email" 
                type="email" 
                placeholder="you@example.com"
                value={email} 
                onChange={(e) => setEmail(e.target.value)} 
                required
                className="h-11 focus:border-[#003D82] bg-gray-50/50"
              />
            </div>
            <div className="space-y-2">
              <div className="flex justify-between items-center">
                <Label htmlFor="login-password">Password</Label>
                <button type="button" className="text-sm text-[#003D82] font-medium hover:underline">Forgot password?</button>
              </div>
              <Input 
                id="login-password" 
                type="password" 
                placeholder="••••••••"
                value={password} 
                onChange={(e) => setPassword(e.target.value)} 
                required
                className="h-11 focus:border-[#003D82] bg-gray-50/50"
              />
            </div>

            <Button type="submit" className="w-full bg-[#003D82] hover:bg-[#002d62] h-12 text-lg shadow-md hover:shadow-lg transition-all mt-2" disabled={loading}>
              {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : (
                  <span className="flex items-center gap-2">
                      <LogIn className="w-5 h-5" /> Sign In
                  </span>
              )}
            </Button>
          </form>

          <div className="mt-8 pt-6 border-t border-gray-100 text-center">
            <p className="text-gray-500 text-sm">
              Don't have an account?{' '}
              <button 
                onClick={onSwitchToSignUp}
                className="text-[#D4AF37] font-bold hover:underline transition-colors"
              >
                Sign up for free
              </button>
            </p>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};

export default LoginModal;