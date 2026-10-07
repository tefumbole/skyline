const PERMISSIONS_KEY = 'alpha_permissions';
const ROLE_PERMISSIONS_KEY = 'alpha_role_permissions';

// ✅ Map DB roles -> local role IDs used by this permission system
const ROLE_ID_MAP = {
  super_admin: 'role-super-admin',
  admin: 'role-admin',
  director: 'role-director',
  manager: 'role-manager',
  applicant: 'role-applicant',
  student: 'role-student',
  shareholder: 'role-shareholder',
};

const defaultPermissions = [
  { id: 'perm-dashboard', name: 'view_dashboard', description: 'Can view dashboard' },
  { id: 'perm-users-manage', name: 'manage_users', description: 'Can manage users' },
  { id: 'perm-roles-manage', name: 'manage_roles', description: 'Can manage roles' },
  { id: 'perm-perms-manage', name: 'manage_permissions', description: 'Can manage permissions' },
  { id: 'perm-shareholders-view', name: 'view_shareholders', description: 'Can view shareholders' },
  { id: 'perm-events-view', name: 'view_events', description: 'Can view events' },
  { id: 'perm-otp-send', name: 'send_otp', description: 'Can send OTPs' },
];

// Initialize local permission storage (fallback permissions system)
const init = () => {
  if (!localStorage.getItem(PERMISSIONS_KEY)) {
    localStorage.setItem(PERMISSIONS_KEY, JSON.stringify(defaultPermissions));
  }

  if (!localStorage.getItem(ROLE_PERMISSIONS_KEY)) {
    // Give ALL permissions to Super Admin by default
    const assignments = defaultPermissions.map((p) => ({
      role_id: ROLE_ID_MAP.super_admin,
      permission_id: p.id,
    }));
    localStorage.setItem(ROLE_PERMISSIONS_KEY, JSON.stringify(assignments));
  }
};

const getPermissions = () => {
  init();
  return JSON.parse(localStorage.getItem(PERMISSIONS_KEY) || '[]');
};

const getAssignments = () => {
  init();
  return JSON.parse(localStorage.getItem(ROLE_PERMISSIONS_KEY) || '[]');
};

// ✅ Helper: accept either "super_admin" OR "role-super-admin"
const normalizeRoleId = (roleOrRoleId) => {
  const raw = String(roleOrRoleId || '').trim().toLowerCase();
  if (!raw) return '';

  // already a role-id?
  if (raw.startsWith('role-')) return raw;

  // db role -> role-id
  return ROLE_ID_MAP[raw] || raw;
};

export const getAllPermissions = async () => {
  return getPermissions();
};

export const getPermissionsByRole = async (roleIdOrRoleName) => {
  const roleId = normalizeRoleId(roleIdOrRoleName);

  const assignments = getAssignments();
  const roleAssigns = assignments.filter((a) => a.role_id === roleId);

  const allPerms = getPermissions();
  return allPerms.filter((p) => roleAssigns.some((ra) => ra.permission_id === p.id));
};

export const assignPermissionToRole = async (roleIdOrRoleName, permissionId) => {
  const roleId = normalizeRoleId(roleIdOrRoleName);

  const assignments = getAssignments();
  if (!assignments.some((a) => a.role_id === roleId && a.permission_id === permissionId)) {
    assignments.push({ role_id: roleId, permission_id: permissionId });
    localStorage.setItem(ROLE_PERMISSIONS_KEY, JSON.stringify(assignments));
  }
};

export const removePermissionFromRole = async (roleIdOrRoleName, permissionId) => {
  const roleId = normalizeRoleId(roleIdOrRoleName);

  let assignments = getAssignments();
  assignments = assignments.filter((a) => !(a.role_id === roleId && a.permission_id === permissionId));
  localStorage.setItem(ROLE_PERMISSIONS_KEY, JSON.stringify(assignments));
};

export const createPermission = async (permData) => {
  const perms = getPermissions();
  const newPerm = { id: `perm-${Date.now()}`, ...permData };
  perms.push(newPerm);
  localStorage.setItem(PERMISSIONS_KEY, JSON.stringify(perms));
  return newPerm;
};

// Optional export if PermissionContext wants it
export const toRoleId = (roleOrRoleId) => normalizeRoleId(roleOrRoleId);