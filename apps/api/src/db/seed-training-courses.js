import { randomUUID } from 'node:crypto';
import { trainingModules } from '../../../../src/data/trainingData.js';

function moduleToRow(module, sortOrder) {
  return {
    name: module.title,
    description: `${module.category} professional training program.`,
    price: 300,
    duration: module.duration,
    category: 'Training',
    delivery_mode: module.deliveryMode,
    icon: module.icon,
    color: module.color,
    curriculum_json: JSON.stringify({ sections: module.sections || [] }),
    sort_order: sortOrder,
    status: 'active',
  };
}

/** Upsert canonical training programs; archive legacy service courses (CCTV, Fiber, etc.). */
export async function seedTrainingCourses(pool) {
  const titles = trainingModules.map((m) => m.title);
  let upserted = 0;

  for (let i = 0; i < trainingModules.length; i += 1) {
    const row = moduleToRow(trainingModules[i], i + 1);
    const [existing] = await pool.query('SELECT id FROM courses WHERE name = ? LIMIT 1', [row.name]);

    if (existing.length) {
      await pool.query(
        `UPDATE courses SET
          description = ?, price = ?, duration = ?, category = ?, delivery_mode = ?,
          icon = ?, color = ?, curriculum_json = ?, sort_order = ?, status = 'active', updated_at = NOW()
         WHERE id = ?`,
        [
          row.description,
          row.price,
          row.duration,
          row.category,
          row.delivery_mode,
          row.icon,
          row.color,
          row.curriculum_json,
          row.sort_order,
          existing[0].id,
        ]
      );
    } else {
      const id = randomUUID();
      await pool.query(
        `INSERT INTO courses
          (id, name, description, price, duration, category, delivery_mode, icon, color, curriculum_json, sort_order, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')`,
        [
          id,
          row.name,
          row.description,
          row.price,
          row.duration,
          row.category,
          row.delivery_mode,
          row.icon,
          row.color,
          row.curriculum_json,
          row.sort_order,
        ]
      );
    }
    upserted += 1;
  }

  const placeholders = titles.map(() => '?').join(', ');
  const [archived] = await pool.query(
    `UPDATE courses SET status = 'archived', sort_order = 9999, updated_at = NOW()
     WHERE status != 'archived' AND name NOT IN (${placeholders})`,
    titles
  );

  console.log(
    `Training courses seeded: ${upserted} program(s), ${archived.affectedRows || 0} legacy course(s) archived.`
  );
}
