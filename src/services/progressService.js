import { supabase } from '@/lib/customSupabaseClient';
import { generateCertificate } from './certificatesService';

export const getProgress = async (registrationId) => {
    try {
        const { data, error } = await supabase
            .from('student_progress')
            .select('*')
            .eq('registration_id', registrationId);
        if (error) throw error;

        const rows = data || [];
        if (!rows.length) return rows;

        const courseIds = [...new Set(rows.map((r) => r.course_id).filter(Boolean))];
        const { data: courses } = await supabase.from('courses').select('id, name').in('id', courseIds);
        const courseMap = Object.fromEntries((courses || []).map((c) => [c.id, c]));
        return rows.map((row) => ({ ...row, courses: courseMap[row.course_id] || null }));
    } catch (error) {
        console.error("Error getting progress:", error);
        throw error;
    }
};

export const getAllStudentProgress = async () => {
    try {
        const { data, error } = await supabase
            .from('student_progress')
            .select('*');
        if (error) throw error;

        const rows = data || [];
        if (!rows.length) return rows;

        const regIds = [...new Set(rows.map((r) => r.registration_id).filter(Boolean))];
        const courseIds = [...new Set(rows.map((r) => r.course_id).filter(Boolean))];

        const [{ data: registrations }, { data: courses }] = await Promise.all([
          supabase.from('registrations').select('id, client_name').in('id', regIds),
          supabase.from('courses').select('id, name').in('id', courseIds),
        ]);

        const regMap = Object.fromEntries((registrations || []).map((r) => [r.id, r]));
        const courseMap = Object.fromEntries((courses || []).map((c) => [c.id, c]));

        return rows.map((row) => ({
          ...row,
          registrations: regMap[row.registration_id] || null,
          courses: courseMap[row.course_id] || null,
        }));
    } catch (error) {
         console.error("Error getting all progress:", error);
        throw error;
    }
};

export const updateProgress = async (registrationId, courseId, progress, status) => {
    try {
        // Upsert progress
        const updates = {
            registration_id: registrationId,
            course_id: courseId,
            progress_percentage: progress,
            status: status,
            last_updated: new Date().toISOString()
        };

        if (status === 'completed') {
            updates.completion_date = new Date().toISOString();
        } else if (status === 'in_progress' && progress > 0) {
            updates.start_date = new Date().toISOString(); // Only set start if not set, logic simplified
        }

        // Check if exists
        const { data: existing } = await supabase
            .from('student_progress')
            .select('id')
            .eq('registration_id', registrationId)
            .eq('course_id', courseId)
            .single();

        let result;
        if (existing) {
             const { data, error } = await supabase
                .from('student_progress')
                .update(updates)
                .eq('id', existing.id)
                .select()
                .single();
             if(error) throw error;
             result = data;
        } else {
             const { data, error } = await supabase
                .from('student_progress')
                .insert([updates])
                .select()
                .single();
             if(error) throw error;
             result = data;
        }

        // Auto Generate Certificate on Completion
        if (status === 'completed') {
            // Fetch registration info for name
            const { data: reg } = await supabase.from('registrations').select('client_name').eq('id', registrationId).single();
            const { data: course } = await supabase.from('courses').select('name').eq('id', courseId).single();
            
            await generateCertificate(
                { registration_id: registrationId, student_name: reg.client_name },
                { name: course.name }
            );
        }

        return result;
    } catch (error) {
        console.error("Error updating progress:", error);
        throw error;
    }
};