import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';
import { createRegistration } from '@/services/registrationsService';
import { Loader2, CheckCircle, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';

const CourseRegistrationForm = ({ selectedCourses, totalAmount, onReset }) => {
    const [formData, setFormData] = useState({
        client_name: '',
        client_email: '',
        client_phone: ''
    });
    const [loading, setLoading] = useState(false);
    const [completed, setCompleted] = useState(false);
    const { toast } = useToast();

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (selectedCourses.length === 0) {
            toast({ 
                title: "No Courses Selected", 
                description: "Please select at least one course to register.", 
                variant: "destructive" 
            });
            return;
        }

        if (!formData.client_name || !formData.client_email || !formData.client_phone) {
            toast({ 
                title: "Missing Information", 
                description: "Please fill in all contact details.", 
                variant: "destructive" 
            });
            return;
        }

        setLoading(true);
        try {
            const registrationData = {
                ...formData,
                total_price: totalAmount
            };
            
            const courseIds = selectedCourses.map(c => c.id);
            await createRegistration(registrationData, courseIds);
            
            setCompleted(true);
            toast({ 
                title: "Registration Successful", 
                description: "We have received your registration details.",
                className: "bg-green-50 border-green-200 text-green-900"
            });
        } catch (error) {
            console.error("Registration error:", error);
            toast({ 
                title: "Registration Failed", 
                description: "Please try again or contact support.", 
                variant: "destructive" 
            });
        } finally {
            setLoading(false);
        }
    };

    if (completed) {
        return (
            <Card className="border-green-100 shadow-lg bg-green-50/50">
                <CardContent className="pt-8 pb-8 text-center space-y-4">
                    <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <CheckCircle className="w-8 h-8 text-green-600" />
                    </div>
                    <h3 className="text-2xl font-bold text-green-800">Registration Complete!</h3>
                    <p className="text-green-700 max-w-md mx-auto">
                        Thank you, {formData.client_name}. Your registration for {selectedCourses.length} course(s) has been confirmed.
                    </p>
                    <div className="bg-white p-4 rounded-lg border border-green-100 max-w-sm mx-auto text-left mt-4">
                        <p className="text-sm text-gray-500 mb-1">Total Amount Due</p>
                        <p className="text-xl font-bold text-[#003D82]">${totalAmount.toLocaleString()}</p>
                    </div>
                    <Button onClick={onReset} className="mt-6 bg-green-600 hover:bg-green-700 text-white">
                        Register Another Student
                    </Button>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="border-t-4 border-t-[#003D82] shadow-xl">
            <CardHeader className="bg-gray-50/50 pb-4">
                <CardTitle className="text-[#003D82] text-xl">Confirm Registration</CardTitle>
                <CardDescription>Enter your details to finalize enrollment.</CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <div className="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <h4 className="font-semibold text-blue-900 mb-2 flex justify-between items-center">
                        <span>Selected Courses ({selectedCourses.length})</span>
                        <span className="text-lg font-bold">${totalAmount.toLocaleString()}</span>
                    </h4>
                    <ul className="space-y-1 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                        {selectedCourses.map(course => (
                            <li key={course.id} className="text-sm text-blue-700 flex justify-between">
                                <span className="truncate pr-4">{course.name}</span>
                                <span>${Number(course.price).toLocaleString()}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="client_name">Full Name <span className="text-red-500">*</span></Label>
                        <Input 
                            id="client_name" 
                            name="client_name" 
                            value={formData.client_name} 
                            onChange={handleChange} 
                            placeholder="John Doe"
                            required
                        />
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="client_email">Email Address <span className="text-red-500">*</span></Label>
                            <Input 
                                id="client_email" 
                                name="client_email" 
                                type="email"
                                value={formData.client_email} 
                                onChange={handleChange} 
                                placeholder="john@example.com"
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="client_phone">Phone Number <span className="text-red-500">*</span></Label>
                            <Input 
                                id="client_phone" 
                                name="client_phone" 
                                value={formData.client_phone} 
                                onChange={handleChange} 
                                placeholder="+1 (555) 000-0000"
                                required
                            />
                        </div>
                    </div>

                    <Button 
                        type="submit" 
                        className={cn(
                            "w-full mt-6 py-6 text-lg font-semibold bg-[#D4AF37] hover:bg-[#b5952f] text-[#003D82] transition-all",
                            loading && "opacity-80"
                        )}
                        disabled={loading || selectedCourses.length === 0}
                    >
                        {loading ? (
                            <><Loader2 className="w-5 h-5 mr-2 animate-spin" /> Processing...</>
                        ) : (
                            <><span className="mr-2">Submit Registration</span> <ArrowRight className="w-5 h-5" /></>
                        )}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
};

export default CourseRegistrationForm;