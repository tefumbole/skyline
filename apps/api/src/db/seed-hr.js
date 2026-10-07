import { randomUUID } from 'node:crypto';

const CATEGORIES = [
  { code: 'contract', name: 'Contract Staff', description: 'Engaged for a specific contract period, project, or agreed salary.' },
  { code: 'permanent', name: 'Permanent Staff', description: 'Fixed monthly salary employees.' },
  { code: 'job_based', name: 'Job-Based Staff', description: 'Engineers, technicians, workers paid per job or per day.' },
];

const ALLOWANCE_TYPES = [
  { code: 'food', name: 'Food Allowance', default_amount: 0 },
  { code: 'transport', name: 'Transport Allowance', default_amount: 0 },
  { code: 'overtime', name: 'Overtime Allowance', default_amount: 0 },
  { code: 'accommodation', name: 'Accommodation Allowance', default_amount: 0 },
  { code: 'communication', name: 'Communication Allowance', default_amount: 0 },
  { code: 'special', name: 'Special Assignment Allowance', default_amount: 0 },
];

const DEDUCTION_TYPES = [
  { code: 'absence', name: 'Absence Deduction', default_amount: 0 },
  { code: 'late', name: 'Late Coming Deduction', default_amount: 0 },
  { code: 'advance', name: 'Advance Payment Deduction', default_amount: 0 },
  { code: 'loan', name: 'Loan Deduction', default_amount: 0 },
  { code: 'damages', name: 'Damages Deduction', default_amount: 0 },
  { code: 'disciplinary', name: 'Disciplinary Deduction', default_amount: 0 },
  { code: 'other', name: 'Other Deductions', default_amount: 0 },
];

const POSITION_RATES = [
  { position: 'Unskilled Labour / Manoeuvre', daily_rate: 2500 },
  { position: 'Worker', daily_rate: 3500 },
  { position: 'Senior Technician', daily_rate: 4000 },
  { position: 'Engineer', daily_rate: 5000 },
  { position: 'Senior Engineer', daily_rate: 6000 },
];

export async function seedHr(pool) {
  for (const cat of CATEGORIES) {
    const [existing] = await pool.query('SELECT id FROM hr_staff_categories WHERE code = ? LIMIT 1', [cat.code]);
    if (existing.length) continue;
    await pool.query(
      `INSERT INTO hr_staff_categories (id, code, name, description, is_active) VALUES (?, ?, ?, ?, 1)`,
      [randomUUID(), cat.code, cat.name, cat.description]
    );
  }

  for (const a of ALLOWANCE_TYPES) {
    const [existing] = await pool.query('SELECT id FROM hr_allowance_types WHERE code = ? LIMIT 1', [a.code]);
    if (existing.length) continue;
    await pool.query(
      `INSERT INTO hr_allowance_types (id, code, name, default_amount, is_active) VALUES (?, ?, ?, ?, 1)`,
      [randomUUID(), a.code, a.name, a.default_amount]
    );
  }

  for (const d of DEDUCTION_TYPES) {
    const [existing] = await pool.query('SELECT id FROM hr_deduction_types WHERE code = ? LIMIT 1', [d.code]);
    if (existing.length) continue;
    await pool.query(
      `INSERT INTO hr_deduction_types (id, code, name, default_amount, is_active) VALUES (?, ?, ?, ?, 1)`,
      [randomUUID(), d.code, d.name, d.default_amount]
    );
  }

  for (const p of POSITION_RATES) {
    const [existing] = await pool.query('SELECT id FROM hr_position_rates WHERE position = ? LIMIT 1', [p.position]);
    if (existing.length) continue;
    await pool.query(
      `INSERT INTO hr_position_rates (id, position, daily_rate, is_active) VALUES (?, ?, ?, 1)`,
      [randomUUID(), p.position, p.daily_rate]
    );
  }

  const staffPerms = [
    'menu.tasks', 'tasks.view', 'tasks.create',
    'menu.jobs', 'jobs.view',
    'menu.timesheets', 'timesheets.view', 'timesheets.create',
  ];
  for (const permission of staffPerms) {
    const [existing] = await pool.query(
      'SELECT id FROM role_permissions WHERE role = ? AND permission = ? LIMIT 1',
      ['Staff', permission]
    );
    if (existing.length) continue;
    await pool.query(
      'INSERT INTO role_permissions (id, role, permission, created_at) VALUES (?, ?, ?, NOW())',
      [randomUUID(), 'Staff', permission]
    );
  }
}
