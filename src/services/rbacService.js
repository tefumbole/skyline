import { supabase } from '@/lib/customSupabaseClient';

/**
 * RBAC Service
 * Role-Based Access Control service for managing roles, permissions, and user roles
 */

/**
 * Get all roles
 * @returns {Promise<Array>} Array of roles
 */
export async function getAllRoles() {
  console.log('[RBAC] Fetching all roles');
  
  try {
    const { data, error } = await supabase
      .from('roles')
      .select(`
        *,
        role_permissions (
          id,
          permission:permissions (
            id,
            name,
            description,
            category,
            is_active
          )
        )
      `)
      .eq('is_active', true)
      .order('name');
    
    if (error) {
      console.error('[RBAC] Error fetching roles:', error);
      throw error;
    }
    
    console.log('[RBAC] Fetched', data?.length || 0, 'roles');
    return data || [];
  } catch (error) {
    console.error('[RBAC] getAllRoles error:', error);
    throw error;
  }
}

/**
 * Get role by ID
 * @param {string} roleId - Role UUID
 * @returns {Promise<Object>} Role object with permissions
 */
export async function getRoleById(roleId) {
  console.log('[RBAC] Fetching role by ID:', roleId);
  
  try {
    const { data, error } = await supabase
      .from('roles')
      .select(`
        *,
        role_permissions (
          id,
          permission:permissions (
            id,
            name,
            description,
            category,
            is_active
          )
        )
      `)
      .eq('id', roleId)
      .single();
    
    if (error) {
      console.error('[RBAC] Error fetching role:', error);
      throw error;
    }
    
    console.log('[RBAC] Role found:', data?.name);
    return data;
  } catch (error) {
    console.error('[RBAC] getRoleById error:', error);
    throw error;
  }
}

/**
 * Get role by name
 * @param {string} roleName - Role name
 * @returns {Promise<Object>} Role object
 */
export async function getRoleByName(roleName) {
  console.log('[RBAC] Fetching role by name:', roleName);
  
  try {
    const { data, error } = await supabase
      .from('roles')
      .select('*')
      .eq('name', roleName)
      .single();
    
    if (error) {
      console.error('[RBAC] Error fetching role:', error);
      throw error;
    }
    
    return data;
  } catch (error) {
    console.error('[RBAC] getRoleByName error:', error);
    throw error;
  }
}

/**
 * Create role
 * @param {Object} payload - Role data
 * @returns {Promise<Object>} Created role
 */
export async function createRole(payload) {
  console.log('[RBAC] Creating role:', payload);
  
  try {
    const { data, error } = await supabase
      .from('roles')
      .insert({
        ...payload,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      })
      .select()
      .single();
    
    if (error) {
      console.error('[RBAC] Error creating role:', error);
      throw error;
    }
    
    console.log('[RBAC] Role created:', data.id);
    return data;
  } catch (error) {
    console.error('[RBAC] createRole error:', error);
    throw error;
  }
}

/**
 * Update role
 * @param {string} roleId - Role UUID
 * @param {Object} payload - Update data
 * @returns {Promise<Object>} Updated role
 */
export async function updateRole(roleId, payload) {
  console.log('[RBAC] Updating role:', roleId, payload);
  
  try {
    const { data, error } = await supabase
      .from('roles')
      .update({
        ...payload,
        updated_at: new Date().toISOString()
      })
      .eq('id', roleId)
      .select()
      .single();
    
    if (error) {
      console.error('[RBAC] Error updating role:', error);
      throw error;
    }
    
    console.log('[RBAC] Role updated:', data.id);
    return data;
  } catch (error) {
    console.error('[RBAC] updateRole error:', error);
    throw error;
  }
}

/**
 * Delete role
 * @param {string} roleId - Role UUID
 * @returns {Promise<void>}
 */
export async function deleteRole(roleId) {
  console.log('[RBAC] Deleting role:', roleId);
  
  try {
    const { error } = await supabase
      .from('roles')
      .delete()
      .eq('id', roleId);
    
    if (error) {
      console.error('[RBAC] Error deleting role:', error);
      throw error;
    }
    
    console.log('[RBAC] Role deleted');
  } catch (error) {
    console.error('[RBAC] deleteRole error:', error);
    throw error;
  }
}

/**
 * Get all permissions
 * @returns {Promise<Array>} Array of permissions
 */
export async function getAllPermissions() {
  console.log('[RBAC] Fetching all permissions');
  
  try {
    const { data, error } = await supabase
      .from('permissions')
      .select('*')
      .eq('is_active', true)
      .order('category', { ascending: true })
      .order('name', { ascending: true });
    
    if (error) {
      console.error('[RBAC] Error fetching permissions:', error);
      throw error;
    }
    
    console.log('[RBAC] Fetched', data?.length || 0, 'permissions');
    return data || [];
  } catch (error) {
    console.error('[RBAC] getAllPermissions error:', error);
    throw error;
  }
}

/**
 * Get role permissions (NEW FUNCTION)
 * @param {string} roleId - Role UUID
 * @returns {Promise<Array>} Array of permissions assigned to role
 */
export async function getRolePermissions(roleId) {
  console.log('[RBAC] Fetching permissions for role:', roleId);
  
  try {
    const { data, error } = await supabase
      .from('role_permissions')
      .select(`
        id,
        permission:permissions (
          id,
          name,
          description,
          category,
          is_active
        )
      `)
      .eq('role_id', roleId);
    
    if (error) {
      console.error('[RBAC] Error fetching role permissions:', error);
      throw error;
    }
    
    console.log('[RBAC] Found', data?.length || 0, 'permissions for role');
    return data || [];
  } catch (error) {
    console.error('[RBAC] getRolePermissions error:', error);
    throw error;
  }
}

/**
 * Assign permission to role (NEW FUNCTION)
 * @param {string} roleId - Role UUID
 * @param {string} permissionId - Permission UUID
 * @returns {Promise<Object>} Created role_permission record
 */
export async function assignPermissionToRole(roleId, permissionId) {
  console.log('[RBAC] Assigning permission to role:', { roleId, permissionId });
  
  try {
    const { data, error } = await supabase
      .from('role_permissions')
      .insert({
        role_id: roleId,
        permission_id: permissionId,
        created_at: new Date().toISOString()
      })
      .select(`
        *,
        permission:permissions (
          id,
          name,
          description,
          category
        )
      `)
      .single();
    
    if (error) {
      console.error('[RBAC] Error assigning permission:', error);
      
      // Handle duplicate constraint error
      if (error.code === '23505') {
        console.log('[RBAC] Permission already assigned to role');
        return { success: true, message: 'Permission already assigned' };
      }
      
      throw error;
    }
    
    console.log('[RBAC] Permission assigned successfully');
    return data;
  } catch (error) {
    console.error('[RBAC] assignPermissionToRole error:', error);
    throw error;
  }
}

/**
 * Remove permission from role (NEW FUNCTION)
 * @param {string} roleId - Role UUID
 * @param {string} permissionId - Permission UUID
 * @returns {Promise<void>}
 */
export async function removePermissionFromRole(roleId, permissionId) {
  console.log('[RBAC] Removing permission from role:', { roleId, permissionId });
  
  try {
    const { error } = await supabase
      .from('role_permissions')
      .delete()
      .eq('role_id', roleId)
      .eq('permission_id', permissionId);
    
    if (error) {
      console.error('[RBAC] Error removing permission:', error);
      throw error;
    }
    
    console.log('[RBAC] Permission removed successfully');
  } catch (error) {
    console.error('[RBAC] removePermissionFromRole error:', error);
    throw error;
  }
}

/**
 * Assign role to user
 * @param {string} userId - User UUID
 * @param {string} roleId - Role UUID
 * @param {string} assignedBy - Assigner user UUID (optional)
 * @returns {Promise<Object>} Created user_role record
 */
export async function assignRoleToUser(userId, roleId, assignedBy = null) {
  console.log('[RBAC] Assigning role to user:', { userId, roleId, assignedBy });
  
  try {
    const { data, error } = await supabase
      .from('user_roles')
      .insert({
        user_id: userId,
        role_id: roleId,
        assigned_by: assignedBy,
        assigned_at: new Date().toISOString()
      })
      .select(`
        *,
        role:roles (
          id,
          name,
          description
        )
      `)
      .single();
    
    if (error) {
      console.error('[RBAC] Error assigning role:', error);
      
      // Handle duplicate constraint error
      if (error.code === '23505') {
        console.log('[RBAC] Role already assigned to user');
        return { success: true, message: 'Role already assigned' };
      }
      
      throw error;
    }
    
    console.log('[RBAC] Role assigned successfully');
    return data;
  } catch (error) {
    console.error('[RBAC] assignRoleToUser error:', error);
    throw error;
  }
}

/**
 * Remove role from user
 * @param {string} userId - User UUID
 * @param {string} roleId - Role UUID
 * @returns {Promise<void>}
 */
export async function removeRoleFromUser(userId, roleId) {
  console.log('[RBAC] Removing role from user:', { userId, roleId });
  
  try {
    const { error } = await supabase
      .from('user_roles')
      .delete()
      .eq('user_id', userId)
      .eq('role_id', roleId);
    
    if (error) {
      console.error('[RBAC] Error removing role:', error);
      throw error;
    }
    
    console.log('[RBAC] Role removed successfully');
  } catch (error) {
    console.error('[RBAC] removeRoleFromUser error:', error);
    throw error;
  }
}

/**
 * Get user roles
 * @param {string} userId - User UUID
 * @returns {Promise<Array>} Array of user roles with details
 */
export async function getUserRoles(userId) {
  console.log('[RBAC] Fetching user roles for:', userId);
  
  try {
    const { data, error } = await supabase
      .from('user_roles')
      .select(`
        *,
        role:roles (
          id,
          name,
          description,
          role_permissions (
            permission:permissions (
              id,
              name,
              description,
              category
            )
          )
        )
      `)
      .eq('user_id', userId);
    
    if (error) {
      console.error('[RBAC] Error fetching user roles:', error);
      throw error;
    }
    
    console.log('[RBAC] Found', data?.length || 0, 'roles for user');
    return data || [];
  } catch (error) {
    console.error('[RBAC] getUserRoles error:', error);
    throw error;
  }
}

/**
 * Get user permissions
 * @param {string} userId - User UUID
 * @returns {Promise<Array>} Array of permission names
 */
export async function getUserPermissions(userId) {
  console.log('[RBAC] Fetching user permissions for:', userId);
  
  try {
    const { data, error } = await supabase
      .from('user_roles')
      .select(`
        role:roles (
          role_permissions (
            permission:permissions (
              name
            )
          )
        )
      `)
      .eq('user_id', userId);
    
    if (error) {
      console.error('[RBAC] Error fetching user permissions:', error);
      throw error;
    }
    
    // Flatten permissions array
    const permissions = [];
    data?.forEach(userRole => {
      userRole.role?.role_permissions?.forEach(rp => {
        if (rp.permission?.name && !permissions.includes(rp.permission.name)) {
          permissions.push(rp.permission.name);
        }
      });
    });
    
    console.log('[RBAC] User has', permissions.length, 'permissions');
    return permissions;
  } catch (error) {
    console.error('[RBAC] getUserPermissions error:', error);
    throw error;
  }
}

/**
 * Check if user has permission
 * @param {string} userId - User UUID
 * @param {string} permissionName - Permission name
 * @returns {Promise<boolean>} True if user has permission
 */
export async function hasPermission(userId, permissionName) {
  console.log('[RBAC] Checking permission:', { userId, permissionName });
  
  try {
    const permissions = await getUserPermissions(userId);
    const hasAccess = permissions.includes(permissionName);
    
    console.log('[RBAC] User has permission:', hasAccess);
    return hasAccess;
  } catch (error) {
    console.error('[RBAC] hasPermission error:', error);
    return false;
  }
}

/**
 * Get all users with their roles
 * @returns {Promise<Array>} Array of users with roles
 */
export async function getAllUsersWithRoles() {
  console.log('[RBAC] Fetching all users with roles');
  
  try {
    const { data, error } = await supabase
      .from('profiles')
      .select(`
        *,
        user_roles (
          id,
          role:roles (
            id,
            name,
            description
          ),
          assigned_at
        )
      `)
      .order('created_at', { ascending: false });
    
    if (error) {
      console.error('[RBAC] Error fetching users with roles:', error);
      throw error;
    }
    
    console.log('[RBAC] Fetched', data?.length || 0, 'users with roles');
    return data || [];
  } catch (error) {
    console.error('[RBAC] getAllUsersWithRoles error:', error);
    throw error;
  }
}

export default {
  getAllRoles,
  getRoleById,
  getRoleByName,
  createRole,
  updateRole,
  deleteRole,
  getAllPermissions,
  getRolePermissions,
  assignPermissionToRole,
  removePermissionFromRole,
  assignRoleToUser,
  removeRoleFromUser,
  getUserRoles,
  getUserPermissions,
  hasPermission,
  getAllUsersWithRoles
};