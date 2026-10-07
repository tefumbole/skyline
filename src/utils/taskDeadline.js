/**
 * Task deadline helpers — overdue is based on date AND time (deadline_time).
 * If no deadline_time is set, the task is due at end of that day (23:59:59 local).
 */

function normalizeDatePart(deadline) {
  if (!deadline) return null;
  const raw = String(deadline).slice(0, 10);
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;
  const parsed = new Date(deadline);
  if (Number.isNaN(parsed.getTime())) return null;
  const y = parsed.getFullYear();
  const m = String(parsed.getMonth() + 1).padStart(2, '0');
  const d = String(parsed.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function getTaskDueDateTime(deadline, deadlineTime) {
  const dateStr = normalizeDatePart(deadline);
  if (!dateStr) return null;

  const hasTime = deadlineTime && String(deadlineTime).trim();
  const timePart = hasTime ? String(deadlineTime).slice(0, 5) : '23:59';
  const [y, mo, day] = dateStr.split('-').map(Number);
  const [hh, mm] = timePart.split(':').map(Number);

  return new Date(
    y,
    mo - 1,
    day,
    hh || 0,
    mm || 0,
    hasTime ? 0 : 59,
    hasTime ? 0 : 999
  );
}

export function isTaskOverdue(deadline, deadlineTime, now = new Date()) {
  const due = getTaskDueDateTime(deadline, deadlineTime);
  if (!due) return false;
  return now > due;
}

function inferActiveStatus(task) {
  const assignments = task?.task_assignments || [];
  if (assignments.some((a) => ['In Progress', 'Accepted'].includes(a.status))) {
    return 'In Progress';
  }
  return 'Pending';
}

/** Display / filter status that respects deadline date + time. */
export function getEffectiveTaskStatus(task, now = new Date()) {
  const stored = String(task?.status || 'Pending');
  if (stored === 'Completed' || stored === 'Scheduled') return stored;
  if (isTaskOverdue(task?.deadline, task?.deadline_time, now)) return 'Overdue';
  if (stored === 'Overdue') return inferActiveStatus(task);
  return stored;
}
