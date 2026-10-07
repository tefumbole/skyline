import { supabase } from '@/lib/customSupabaseClient';

const generateRandomString = (length) => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
};

export const generateReferenceNumber = async () => {
  const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  let unique = false;
  let refNumber = '';
  let attempts = 0;

  while (!unique && attempts < 10) {
    const randomSuffix = generateRandomString(5);
    refNumber = `JOB-${date}-${randomSuffix}`;

    // Check uniqueness
    const { data } = await supabase
      .from('applications')
      .select('id')
      .eq('reference_number', refNumber)
      .maybeSingle();

    if (!data) {
      unique = true;
    }
    attempts++;
  }

  if (!unique) throw new Error("Failed to generate unique reference number");
  return refNumber;
};