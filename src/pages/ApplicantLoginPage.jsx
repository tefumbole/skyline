import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardFooter } from '@/components/ui/card';
import { Loader2, LogIn, AlertCircle, ArrowLeft } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';

const ApplicantLoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const navigate = useNavigate();
  const { loginWithCredentials } = useAuth(); // Assuming this can handle non-admin too, or we use direct supabase here

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });

      if (error) throw error;
      
      // Redirect to dashboard
      navigate('/applicant-dashboard');

    } catch (err) {
      console.error(err);
      setError(err.message || 'Failed to login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <Link to="/" className="absolute top-8 left-8 text-[#003D82] flex items-center gap-2 hover:underline">
         <ArrowLeft className="w-4 h-4" /> Back to Home
      </Link>
      
      <div className="w-full max-w-md space-y-8">
        <div className="text-center">
            <h1 className="text-3xl font-bold text-[#003D82]">Applicant Portal</h1>
            <p className="text-gray-600 mt-2">Sign in to track your job applications</p>
        </div>

        <Card className="shadow-xl border-t-4 border-t-[#D4AF37]">
            <CardHeader className="space-y-1">
                {error && (
                    <div className="bg-red-50 text-red-600 p-3 rounded-md text-sm flex items-center gap-2 mb-4">
                        <AlertCircle className="w-4 h-4" /> {error}
                    </div>
                )}
            </CardHeader>
            <CardContent>
                <form onSubmit={handleLogin} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input 
                            id="email" type="email" placeholder="name@example.com" 
                            value={email} onChange={(e) => setEmail(e.target.value)} required
                            className="focus:border-[#003D82]"
                        />
                    </div>
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="password">Password</Label>
                            <a href="#" className="text-sm text-[#003D82] hover:underline">Forgot password?</a>
                        </div>
                        <Input 
                            id="password" type="password" 
                            value={password} onChange={(e) => setPassword(e.target.value)} required
                            className="focus:border-[#003D82]"
                        />
                    </div>
                    <div className="flex items-center space-x-2">
                        <Checkbox id="remember" />
                        <label htmlFor="remember" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            Remember me
                        </label>
                    </div>

                    <Button type="submit" className="w-full bg-[#003D82] hover:bg-[#002d62] h-11" disabled={loading}>
                        {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <><LogIn className="w-4 h-4 mr-2" /> Sign In</>}
                    </Button>
                </form>
            </CardContent>
            <CardFooter className="justify-center border-t pt-4">
                <p className="text-sm text-gray-500">
                    Don't have an account? <Link to="/apply-now" className="text-[#003D82] font-semibold hover:underline">Apply for a job</Link> to create one.
                </p>
            </CardFooter>
        </Card>
      </div>
    </div>
  );
};

export default ApplicantLoginPage;