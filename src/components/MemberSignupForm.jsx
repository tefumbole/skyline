import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { supabase } from '@/lib/customSupabaseClient';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, UserPlus, Eye, EyeOff } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { Link } from 'react-router-dom';

const MemberSignupForm = ({ onSignupSuccess }) => {
    const { register, handleSubmit, watch, formState: { errors } } = useForm();
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const { user } = useAuth();
    const { toast } = useToast();
    const password = watch('password');

    const onSubmit = async (formData) => {
        setLoading(true);
        try {
            // Check username uniqueness
            const { data: existingUser, error: existingUserError } = await supabase
                .from('profiles')
                .select('username')
                .eq('username', formData.username)
                .maybeSingle(); // Use maybeSingle to avoid error on no rows

            if (existingUser) {
                throw new Error("Username already exists. Please choose another one.");
            }
            if (existingUserError) {
                console.error("Error checking username:", existingUserError);
                // Proceed cautiously or throw depending on strictness
            }

            // Create Supabase Auth user
            const { data: authData, error: authError } = await supabase.auth.signUp({
                email: formData.email,
                password: formData.password,
                options: {
                    data: {
                        full_name: formData.username, // Use username as full_name for now
                        role: 'Shares' // Assign role
                    }
                }
            });

            if (authError) throw authError;

            // Check if session was established (auto-confirm enabled) or if email verification is needed
            if (authData?.session) {
                toast({
                    title: "Account Created!",
                    description: "You have been successfully registered and logged in.",
                    className: "bg-green-50 border-green-200 text-green-900"
                });
                if (onSignupSuccess) {
                    onSignupSuccess(authData.user);
                }
            } else if (authData?.user && !authData?.session) {
                toast({
                    title: "Registration Successful",
                    description: "Please check your email to confirm your account before logging in.",
                    className: "bg-blue-50 border-blue-200 text-blue-900"
                });
                // Optional: Redirect to a 'check email' page or reset form
            }

        } catch (error) {
            console.error("Signup error:", error);
            toast({
                title: "Signup Failed",
                description: error.message,
                variant: "destructive"
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="w-full max-w-md mx-auto">
            <div className="text-center mb-6">
                <UserPlus className="w-10 h-10 mx-auto mb-2 text-[#003D82]" />
                <h2 className="text-2xl font-bold text-gray-900">Create Investor Account</h2>
                <p className="text-gray-500 text-sm">Join our community of shareholders.</p>
            </div>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                <div className="space-y-1">
                    <Label htmlFor="email">Email</Label>
                    <Input id="email" type="email" defaultValue={user?.email} {...register('email', { required: 'Email is required' })} />
                    {errors.email && <p className="text-red-500 text-xs">{errors.email.message}</p>}
                </div>
                <div className="space-y-1">
                    <Label htmlFor="username">Username</Label>
                    <Input id="username" {...register('username', { required: 'Username is required', minLength: { value: 3, message: 'Username must be at least 3 characters' } })} />
                    {errors.username && <p className="text-red-500 text-xs">{errors.username.message}</p>}
                </div>
                <div className="space-y-1 relative">
                    <Label htmlFor="password">Password</Label>
                    <Input id="password" type={showPassword ? 'text' : 'password'} {...register('password', { required: 'Password is required', minLength: { value: 8, message: 'Password must be at least 8 characters' } })} />
                    <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-8 text-gray-400">
                        {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                    {errors.password && <p className="text-red-500 text-xs">{errors.password.message}</p>}
                </div>
                <div className="space-y-1">
                    <Label htmlFor="confirmPassword">Confirm Password</Label>
                    <Input id="confirmPassword" type="password" {...register('confirmPassword', { required: 'Please confirm your password', validate: value => value === password || 'Passwords do not match' })} />
                    {errors.confirmPassword && <p className="text-red-500 text-xs">{errors.confirmPassword.message}</p>}
                </div>
                <div className="flex items-center space-x-2">
                    <Checkbox id="terms" {...register('terms', { required: 'You must accept the terms' })} />
                    <Label htmlFor="terms" className="text-sm">
                        I agree to the <Link to="/shareholders-agreement" target="_blank" className="underline text-[#003D82]">terms and conditions</Link>.
                    </Label>
                </div>
                {errors.terms && <p className="text-red-500 text-xs">{errors.terms.message}</p>}

                <Button type="submit" className="w-full bg-[#003D82] hover:bg-[#002855]" disabled={loading}>
                    {loading ? <Loader2 className="animate-spin w-4 h-4 mr-2" /> : null}
                    Create Account & Proceed
                </Button>
            </form>
        </div>
    );
};

export default MemberSignupForm;