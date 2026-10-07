import { supabase } from '@/lib/customSupabaseClient';

export const uploadCV = async (file, candidateName) => {
  if (!file) throw new Error("No file provided");
  
  // Validate File Type
  if (file.type !== 'application/pdf') {
    throw new Error("Only PDF files are allowed");
  }

  // Validate File Size (5MB)
  if (file.size > 5 * 1024 * 1024) {
    throw new Error("File size must be less than 5MB");
  }

  // Get current user to ensure ownership if RLS is enforced
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) throw new Error("Authentication required to upload CV");

  const fileExt = file.name.split('.').pop();
  const sanitizedName = candidateName.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
  // Include timestamp to ensure uniqueness
  const fileName = `${user.id}/${sanitizedName}-${Date.now()}.${fileExt}`;
  
  const { error: uploadError, data } = await supabase.storage
    .from('job-applications')
    .upload(fileName, file, {
        cacheControl: '3600',
        upsert: false
    });

  if (uploadError) {
    console.error('Upload CV Error:', uploadError);
    throw new Error("Failed to upload CV: " + uploadError.message);
  }

  // Get Public URL (if bucket is public) or Signed URL
  // Task 3 created bucket with public=true
  const { data: { publicUrl } } = supabase.storage
    .from('job-applications')
    .getPublicUrl(fileName);

  return publicUrl;
};