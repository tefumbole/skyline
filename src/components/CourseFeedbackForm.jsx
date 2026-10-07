import React, { useState } from 'react';
import { submitFeedback } from '@/services/feedbackService';
import StarRating from './StarRating';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useToast } from '@/components/ui/use-toast';
import { Loader2, Send } from 'lucide-react';

const CourseFeedbackForm = ({ registrationId, courseId, studentName, onSubmitted }) => {
    const [rating, setRating] = useState(0);
    const [feedbackText, setFeedbackText] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const { toast } = useToast();

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (rating === 0) {
            toast({ 
                title: "Rating required", 
                description: "Please select a star rating before submitting.",
                variant: "destructive" 
            });
            return;
        }

        setSubmitting(true);
        try {
            await submitFeedback(registrationId, courseId, studentName, rating, feedbackText);
            toast({ 
                title: "Feedback Submitted", 
                description: "Thank you for your feedback! It helps us improve.",
            });
            setRating(0);
            setFeedbackText('');
            if (onSubmitted) onSubmitted();
        } catch (error) {
            toast({ 
                title: "Submission Failed", 
                description: "There was an error submitting your feedback. Please try again.", 
                variant: "destructive" 
            });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4 bg-white p-6 rounded-lg border border-gray-100 shadow-sm">
            <div>
                <Label className="text-base font-semibold text-gray-700">How would you rate this course?</Label>
                <div className="mt-2">
                    <StarRating rating={rating} onRatingChange={setRating} size={32} />
                </div>
            </div>

            <div>
                <Label htmlFor="feedback" className="text-sm font-medium text-gray-600">Additional Comments (Optional)</Label>
                <Textarea
                    id="feedback"
                    placeholder="Tell us what you liked or how we can improve..."
                    className="mt-1 min-h-[100px]"
                    value={feedbackText}
                    onChange={(e) => setFeedbackText(e.target.value)}
                />
            </div>

            <Button 
                type="submit" 
                disabled={submitting} 
                className="w-full bg-[#003D82] hover:bg-[#002d62]"
            >
                {submitting ? (
                    <>
                        <Loader2 className="w-4 h-4 mr-2 animate-spin" /> Submitting...
                    </>
                ) : (
                    <>
                        <Send className="w-4 h-4 mr-2" /> Submit Feedback
                    </>
                )}
            </Button>
        </form>
    );
};

export default CourseFeedbackForm;