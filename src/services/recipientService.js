import { supabase } from '@/lib/customSupabaseClient';

const STAFF_ROLES = [
  'super_admin',
  'admin',
  'director',
  'staff',
  'employee',
  'teacher',
  'manager',
  'user',
];

const CUSTOMER_ROLES = ['shareholder', 'student', 'customer', 'guest'];

function mapUserRow(row, type) {
  return {
    id: row.id,
    name: row.full_name || row.name,
    full_name: row.full_name || row.name,
    email: row.email || '',
    phone: row.phone || row.full_phone_number || row.phone_number || '',
    address: row.address || '',
    role: row.role || type,
    type,
  };
}

async function fetchUsersByRoles(roles) {
  const { data, error } = await supabase
    .from('users')
    .select('id, email, name, phone, role, status')
    .in('role', roles)
    .eq('status', 'active')
    .order('name', { ascending: true });

  if (error) {
    const { data: profileData, error: profileError } = await supabase
      .from('profiles')
      .select('id, full_name, email, phone, role')
      .in('role', roles)
      .order('full_name', { ascending: true });
    if (profileError) throw profileError;
    return (profileData || []).map((p) => mapUserRow({ ...p, name: p.full_name }, p.role));
  }

  return (data || []).map((u) => mapUserRow({ ...u, full_name: u.name }, u.role));
}

export const fetchSystemUsers = async () => {
  try {
    const rows = await fetchUsersByRoles(STAFF_ROLES);
    return rows.map((u) => ({ ...u, type: 'staff' }));
  } catch (error) {
    console.error('Error fetching system users:', error);
    return [];
  }
};

export const fetchShareholders = async () => {
  try {
    const fromUsers = await fetchUsersByRoles(['shareholder']);
    if (fromUsers.length) {
      return fromUsers.map((u) => ({ ...u, type: 'shareholder' }));
    }

    const { data, error } = await supabase
      .from('shareholders')
      .select('id, full_name, name, email, full_phone_number, phone_number, phone, user_id')
      .order('full_name', { ascending: true });

    if (error) throw error;

    return (data || []).map((s) => mapUserRow({
      id: s.user_id || s.id,
      full_name: s.full_name || s.name,
      email: s.email,
      phone: s.full_phone_number || s.phone_number || s.phone,
      role: 'shareholder',
    }, 'shareholder'));
  } catch (error) {
    console.error('Exception fetching shareholders:', error);
    return [];
  }
};

export const fetchStudents = async () => {
  try {
    const fromUsers = await fetchUsersByRoles(['student']);
    if (fromUsers.length) {
      return fromUsers.map((u) => ({ ...u, type: 'student' }));
    }

    const { data, error } = await supabase
      .from('students')
      .select('id, name, email, phone')
      .order('name', { ascending: true });

    if (error) throw error;
    return (data || []).map((s) => mapUserRow({ ...s, full_name: s.name, role: 'student' }, 'student'));
  } catch (error) {
    console.error('Error fetching students:', error);
    return [];
  }
};

/** @deprecated use fetchSystemUsers */
export const fetchStaff = async () => fetchSystemUsers();

export const fetchAllRecipients = async () => {
  const [shareholders, students, staff] = await Promise.all([
    fetchShareholders(),
    fetchStudents(),
    fetchSystemUsers(),
  ]);

  return { shareholders, students, staff };
};

export const fetchAllUsers = async () => {
  try {
    const { data, error } = await supabase
      .from('users')
      .select('id, email, name, phone, role, status')
      .eq('status', 'active')
      .order('name', { ascending: true });

    if (error) {
      const { data: profiles, error: profileError } = await supabase
        .from('profiles')
        .select('id, full_name, email, phone, role')
        .order('full_name', { ascending: true });
      if (profileError) throw profileError;
      return (profiles || []).map((p) => mapUserRow({ ...p, name: p.full_name }, p.role || 'user'));
    }

    return (data || []).map((u) => mapUserRow({ ...u, full_name: u.name }, u.role));
  } catch (error) {
    console.error('Error fetching all users:', error);
    return [];
  }
};

export { STAFF_ROLES, CUSTOMER_ROLES };
