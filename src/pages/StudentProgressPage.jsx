import React, { useState, useEffect } from 'react';
import { getProgress } from '@/services/progressService';
import { getStudentFeedback } from '@/services/feedbackService';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Download, Award, Clock, CheckCircle } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import CourseFeedbackForm from '@/components/CourseFeedbackForm';
import { useAuth } from '@/context/AuthContext';
// Note: In real app, we need auth context to get current student's ID/Registration
// For demo, assuming the user might not be fully linked, using a placeholder if needed, 
// but attempting to use auth if available.

const StudentProgressPage = () => {
    const [progressData, setProgressData] = useState([]);
    const [submittedFeedback, setSubmittedFeedback] = useState([]);
    const [loading, setLoading] = useState(true);
    const { toast } = useToast();
    // In a real scenario, we'd fetch the registration ID associated with the logged-in user.
    // Since this is a demo environment, I'll simulate a registration ID or use a hardcoded one for testing if not available.
    // Ideally: const { user } = useAuth(); const registrationId = user?.registration_id;
    const registrationId = 'DEMO_REGISTRATION_ID'; 

    useEffect(() => {
        // Simulating data fetch for demo purposes as we don't have a full student login flow in this snippet context
        // In production: fetch real data
        const loadData = async () => {
            try {
                // Mock data for UI demonstration since we can't easily login as student in this specific flow without setup
                // Replace with real calls:
                // const pData = await getProgress(registrationId);
                // const fData = await getStudentFeedback(registrationId);
                
                // Using mock data to demonstrate the UI features requested
                const mockProgress = [
                    { id: 1, registration_id: registrationId, course_id: 'c1', status: 'completed', progress_percentage: 100, courses: { id: 'c1', name: 'Advanced React Patterns' }, start_date: '2023-01-01', completion_date: '2023-02-01' },
                    { id: 2, registration_id: registrationId, course_id: 'c2', status: 'in_progress', progress_percentage: 60, courses: { id: 'c2', name: 'System Design Interview' }, start_date: '2023-02-15' }
                ];
                setProgressData(mockProgress);
                
                // Check for existing feedback
                // const fData = await getStudentFeedback(registrationId);
                setSubmittedFeedback([]); // Empty for demo to show form
                
                setLoading(false);
            } catch (e) {
                console.error(e);
                setLoading(false);
            }
        };
        loadData();
    }, []);

    const handleFeedbackSubmitted = (courseId) => {
        setSubmittedFeedback(prev => [...prev, { course_id: courseId }]);
    };

    const hasFeedback = (courseId) => {
        return submittedFeedback.some(f => f.course_id === courseId);
    };

    return (
        <div className="max-w-4xl mx-auto p-6 space-y-8 animate-in fade-in">
            <h1 className="text-3xl font-bold text-[#003D82]">My Learning Progress</h1>
            
            <div className="grid gap-6">
                {progressData.map((item) => (
                    <Card key={item.id} className="overflow-hidden border-t-4 border-t-[#003D82]">
                        <CardHeader className="bg-gray-50 pb-4">
                            <div className="flex justify-between items-start">
                                <div>
                                    <CardTitle className="text-xl text-gray-800">{item.courses?.name}</CardTitle>
                                    <div className="text-sm text-gray-500 mt-1 flex items-center gap-2">
                                        <Clock className="w-4 h-4" /> 
                                        Started: {new Date(item.start_date).toLocaleDateString()}
                                    </div>
                                </div>
                                <Badge variant={item.status === 'completed' ? 'default' : 'secondary'} 
                                       className={item.status === 'completed' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-100 text-blue-800'}>
                                    {item.status === 'completed' ? 'Completed' : 'In Progress'}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6 space-y-6">
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm font-medium">
                                    <span>Progress</span>
                                    <span>{item.progress_percentage}%</span>
                                </div>
                                <Progress value={item.progress_percentage} className="h-2" />
                            </div>

                            {item.status === 'completed' && (
                                <div className="mt-6 pt-6 border-t border-gray-100">
                                    {!hasFeedback(item.course_id) ? (
                                        <div className="bg-blue-50 rounded-lg p-6">
                                            <h3 className="font-semibold text-[#003D82] mb-2 flex items-center gap-2">
                                                <Award className="w-5 h-5" /> Share your feedback
                                            </h3>
                                            <p className="text-sm text-gray-600 mb-4">
                                                Congratulations on completing the course! Please take a moment to rate your experience.
                                            </p>
                                            <CourseFeedbackForm 
                                                registrationId={item.registration_id}
                                                courseId={item.course_id}
                                                studentName="Student Name" // Should come from auth/profile
                                                onSubmitted={() => handleFeedbackSubmitted(item.course_id)}
                                            />
                                        </div>
                                    ) : (
                                        <div className="bg-green-50 rounded-lg p-4 flex items-center gap-3 text-green-800">
                                            <CheckCircle className="w-5 h-5" />
                                            <span className="font-medium">Thank you for your feedback!</span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
};

export default StudentProgressPage;