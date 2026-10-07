import { supabase } from '@/lib/customSupabaseClient';

/**
 * Service to manage course feedback.
 */

// Submit new feedback
export const submitFeedback = async (registrationId, courseId, studentName, rating, feedbackText) => {
    try {
        const { data, error } = await supabase
            .from('course_feedback')
            .insert([{
                registration_id: registrationId,
                course_id: courseId,
                student_name: studentName,
                rating: Number(rating),
                feedback_text: feedbackText,
                status: 'pending'
            }])
            .select()
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error submitting feedback:", error);
        throw error;
    }
};

// Get feedback for a specific course
export const getFeedbackByCourse = async (courseId) => {
    try {
        const { data, error } = await supabase
            .from('course_feedback')
            .select('*')
            .eq('course_id', courseId)
            .order('created_at', { ascending: false });

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error fetching course feedback:", error);
        throw error;
    }
};

// Get feedback submitted by a specific student (registration)
export const getStudentFeedback = async (registrationId) => {
    try {
        const { data, error } = await supabase
            .from('course_feedback')
            .select('*')
            .eq('registration_id', registrationId);

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error fetching student feedback:", error);
        throw error;
    }
};

// Update feedback status or content
export const updateFeedback = async (feedbackId, updates) => {
    try {
        const { data, error } = await supabase
            .from('course_feedback')
            .update({ ...updates, updated_at: new Date().toISOString() })
            .eq('id', feedbackId)
            .select()
            .single();

        if (error) throw error;
        return data;
    } catch (error) {
        console.error("Error updating feedback:", error);
        throw error;
    }
};

// Delete feedback
export const deleteFeedback = async (feedbackId) => {
    try {
        const { error } = await supabase
            .from('course_feedback')
            .delete()
            .eq('id', feedbackId);

        if (error) throw error;
        return true;
    } catch (error) {
        console.error("Error deleting feedback:", error);
        throw error;
    }
};

// Calculate feedback statistics for a course
export const getCourseFeedbackStats = async (courseId) => {
    try {
        const { data, error } = await supabase
            .from('course_feedback')
            .select('rating')
            .eq('course_id', courseId);

        if (error) throw error;

        if (!data || data.length === 0) {
            return {
                averageRating: 0,
                totalReviews: 0,
                ratingDistribution: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
            };
        }

        const totalReviews = data.length;
        const sumRating = data.reduce((acc, curr) => acc + Number(curr.rating), 0);
        const averageRating = (sumRating / totalReviews).toFixed(1);
        
        const ratingDistribution = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
        data.forEach(item => {
            const r = Math.round(Number(item.rating));
            if (ratingDistribution[r] !== undefined) {
                ratingDistribution[r]++;
            }
        });

        return {
            averageRating: Number(averageRating),
            totalReviews,
            ratingDistribution
        };
    } catch (error) {
        console.error("Error fetching feedback stats:", error);
        return { averageRating: 0, totalReviews: 0, ratingDistribution: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 } };
    }
};

export const getAllFeedback = async () => {
     try {
        const { data, error } = await supabase
            .from('course_feedback')
            .select('*')
            .order('created_at', { ascending: false });

        if (error) throw error;

        const feedback = data || [];
        if (!feedback.length) return feedback;

        const { getTrainingCourses } = await import('@/services/coursesService');
        const courses = await getTrainingCourses();
        const courseMap = Object.fromEntries(courses.map((c) => [c.id, c.name]));

        return feedback.map((row) => ({
          ...row,
          courses: row.course_id ? { name: courseMap[row.course_id] || 'Unknown course' } : null,
        }));
    } catch (error) {
        console.error("Error fetching all feedback:", error);
        throw error;
    }
};