import { trainingModules } from '@/data/trainingData';

export function parseCurriculumJson(raw) {
  if (!raw) return { sections: [] };
  if (typeof raw === 'object') return raw.sections ? raw : { sections: raw };
  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) return { sections: parsed };
    return parsed?.sections ? parsed : { sections: [] };
  } catch {
    return { sections: [] };
  }
}

export function mapCourseToTrainingProgram(course, index = 0) {
  const curriculum = parseCurriculumJson(course.curriculum_json);
  return {
    id: course.id,
    title: course.name,
    description: course.description || '',
    duration: course.duration || 'Flexible schedule',
    deliveryMode: course.delivery_mode || course.category || 'Training',
    color: course.color || ['#003D82', '#0066CC', '#D4AF37', '#0052A3'][index % 4],
    icon: course.icon || 'Briefcase',
    price: course.price,
    sections: curriculum.sections || [],
  };
}

export function mapTrainingModuleToCoursePayload(module, sortOrder) {
  return {
    name: module.title,
    description: `${module.category} professional training program.`,
    price: 300,
    duration: module.duration,
    category: 'Training',
    delivery_mode: module.deliveryMode,
    icon: module.icon,
    color: module.color,
    curriculum_json: { sections: module.sections || [] },
    sort_order: sortOrder,
    status: 'active',
  };
}

export function getTrainingModuleTitles() {
  return trainingModules.map((m) => m.title);
}

/** True when DB courses do not match the canonical 7 training programs. */
export function needsTrainingSync(courses = []) {
  const titles = getTrainingModuleTitles();
  const active = courses.filter((c) => c.status !== 'archived');
  const activeTraining = active.filter((c) => titles.includes(c.name));

  if (activeTraining.length !== titles.length) return true;
  if (active.some((c) => !titles.includes(c.name))) return true;

  return activeTraining.some((course) => {
    const curriculum = parseCurriculumJson(course.curriculum_json);
    return !(curriculum.sections?.length);
  });
}

export { trainingModules };
