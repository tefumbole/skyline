import { randomUUID } from 'node:crypto';
import { getAllPermissionIds } from '../../../../src/config/permissionCatalog.js';

const DEFAULT_ROLES = [
  { name: 'Super Admin', description: 'Full system access with all permissions', is_default: true },
  { name: 'Administrator', description: 'Administrative access to most features', is_default: true },
  { name: 'Manager', description: 'Operational management access', is_default: true },
  { name: 'Staff', description: 'Standard staff member access', is_default: true },
  { name: 'User', description: 'Basic user access', is_default: true },
];

export async function seedRbac(pool) {
  const permissionIds = getAllPermissionIds();
  let rolesUpserted = 0;

  for (const role of DEFAULT_ROLES) {
    const [existing] = await pool.query('SELECT id FROM roles WHERE name = ? LIMIT 1', [role.name]);
    let roleId;

    if (existing.length) {
      roleId = existing[0].id;
      await pool.query(
        `UPDATE roles SET description = ?, is_default = ?, created_at = COALESCE(created_at, NOW()) WHERE id = ?`,
        [role.description, role.is_default ? 1 : 0, roleId]
      );
    } else {
      roleId = randomUUID();
      await pool.query(
        `INSERT INTO roles (id, name, description, is_default, created_at) VALUES (?, ?, ?, ?, NOW())`,
        [roleId, role.name, role.description, role.is_default ? 1 : 0]
      );
    }
    rolesUpserted += 1;

    if (role.name === 'Super Admin') {
      for (const permission of permissionIds) {
        const [permRow] = await pool.query(
          'SELECT id FROM role_permissions WHERE role = ? AND permission = ? LIMIT 1',
          [role.name, permission]
        );
        if (!permRow.length) {
          await pool.query(
            'INSERT INTO role_permissions (id, role, permission, created_at) VALUES (?, ?, ?, NOW())',
            [randomUUID(), role.name, permission]
          );
        }
      }
    }
  }

  console.log(`RBAC seeded: ${rolesUpserted} default role(s), ${permissionIds.length} permission key(s) for Super Admin.`);
}
