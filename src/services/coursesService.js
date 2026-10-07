import { supabase } from "@/lib/customSupabaseClient";

const AUTH_STORAGE_KEY = 'alpha_supabase_auth';

function isLoggedInAdmin() {
  try {
    const raw = localStorage.getItem(AUTH_STORAGE_KEY);
    if (!raw) return false;
    const parsed = JSON.parse(raw);
    const user = parsed?.user || parsed?.currentSession?.user;
    return user?.role === 'admin' || user?.role === 'super_admin';
  } catch {
    return false;
  }
}

/**
 * Service to manage courses in Supabase.
 * Uses array results (no .single / .maybeSingle) to avoid PGRST116 completely.
 */

// Fetch all courses (including archived legacy rows)
export const getCourses = async () => {
  try {
    const { data, error } = await supabase
      .from("courses")
      .select("*")
      .order("sort_order", { ascending: true })
      .order("name", { ascending: true });

    if (error) throw error;
    return data || [];
  } catch (error) {
    console.error("Error fetching courses:", error);
    throw new Error(error.message || "Could not fetch courses list.");
  }
};

// Fetch a single course by ID
export const getCourse = async (id) => {
  try {
    if (!id) throw new Error("Course ID is required");

    const { data, error } = await supabase
      .from("courses")
      .select("*")
      .eq("id", id)
      .limit(1);

    if (error) throw error;

    const course = data?.[0] ?? null;
    if (!course) {
      throw new Error("Course not found (or access denied).");
    }

    return course;
  } catch (error) {
    console.error("Error fetching course details:", error);
    throw new Error(error.message || "Could not fetch course details.");
  }
};

// Create a new course
export const createCourse = async (courseData) => {
  try {
    if (!courseData?.name || courseData?.price === undefined) {
      throw new Error("Name and Price are required.");
    }

    const payload = {
      ...courseData,
      price: parseFloat(courseData.price),
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    const { data, error } = await supabase
      .from("courses")
      .insert([payload])
      .select("*");

    if (error) throw error;

    const created = data?.[0] ?? null;
    if (!created) throw new Error("Course created, but no row returned (check RLS).");

    return created;
  } catch (error) {
    console.error("Error creating course:", error);
    throw new Error(error.message || "Failed to create course.");
  }
};

// Update an existing course (WITH DEBUG LOG)
export const updateCourse = async (id, updates) => {
  try {
    if (!id) throw new Error("Course ID required");

    const payload = {
      ...updates,
      updated_at: new Date().toISOString(),
    };

    // Only parse price if it is actually provided
    if (payload.price !== undefined && payload.price !== null && payload.price !== "") {
      payload.price = parseFloat(payload.price);
    }

    // ✅ DEBUG (this is what you wanted as complete code)
    console.log("updateCourse() id =", id, "payload =", payload);

    const { data, error } = await supabase
      .from("courses")
      .update(payload)
      .eq("id", id)
      .select("*");

    if (error) throw error;

    const updated = data?.[0] ?? null;
    if (!updated) {
      throw new Error("Update failed: course not found (or you don't have permission).");
    }

    return updated;
  } catch (error) {
    console.error("Error updating course:", error);
    throw new Error(error.message || "Failed to update course.");
  }
};

// Delete a course
export const deleteCourse = async (id) => {
  try {
    if (!id) throw new Error("Course ID required");

    const { error } = await supabase
      .from("courses")
      .delete()
      .eq("id", id);

    if (error) throw error;
    return true;
  } catch (error) {
    console.error("Error deleting course:", error);
    throw new Error(error.message || "Failed to delete course.");
  }
};

export const reorderCourses = async (orderedIds = []) => {
  for (let i = 0; i < orderedIds.length; i += 1) {
    await updateCourse(orderedIds[i], { sort_order: i + 1 });
  }
  return true;
};

/** Replace course list with canonical training programs from trainingData.js */
export const syncTrainingCourses = async () => {
  const { trainingModules, mapTrainingModuleToCoursePayload, getTrainingModuleTitles } = await import(
    '@/utils/trainingCourseUtils'
  );

  const existing = await getCourses();
  const trainingTitles = new Set(getTrainingModuleTitles());
  const results = { updated: 0, created: 0, removed: 0, archived: 0 };

  for (let i = 0; i < trainingModules.length; i += 1) {
    const payload = mapTrainingModuleToCoursePayload(trainingModules[i], i + 1);
    const match = existing.find((c) => c.name === payload.name);
    if (match) {
      await updateCourse(match.id, payload);
      results.updated += 1;
    } else {
      await createCourse(payload);
      results.created += 1;
    }
  }

  for (const course of existing) {
    if (trainingTitles.has(course.name)) continue;
    try {
      await deleteCourse(course.id);
      results.removed += 1;
    } catch {
      await updateCourse(course.id, { status: 'archived', sort_order: 9999 });
      results.archived += 1;
    }
  }

  return results;
};

export async function ensureTrainingCoursesSynced() {
  const { needsTrainingSync } = await import('@/utils/trainingCourseUtils');
  const existing = await getCourses();
  if (!needsTrainingSync(existing)) return null;
  if (!isLoggedInAdmin()) return null;
  return syncTrainingCourses();
}

export async function getTrainingCourses(options = {}) {
  const { sync = true } = options;
  const { getTrainingModuleTitles } = await import('@/utils/trainingCourseUtils');
  const titles = new Set(getTrainingModuleTitles());

  if (sync) {
    await ensureTrainingCoursesSynced();
  }

  const courses = await getCourses();
  return courses
    .filter((c) => c.status !== 'archived' && titles.has(c.name))
    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.name.localeCompare(b.name));
}

/** Single source for public Training page and admin Course list display shape. */
export async function getTrainingPrograms() {
  const {
    mapCourseToTrainingProgram,
    trainingModules,
    needsTrainingSync,
    getTrainingModuleTitles,
  } = await import('@/utils/trainingCourseUtils');

  const fallback = () => trainingModules.map((m) => ({ ...m, id: m.id }));

  try {
    let courses = await getCourses();

    if (needsTrainingSync(courses) && isLoggedInAdmin()) {
      try {
        await syncTrainingCourses();
        courses = await getCourses();
      } catch (error) {
        console.warn('Training sync failed:', error.message);
      }
    }

    if (needsTrainingSync(courses)) {
      return fallback();
    }

    const titles = new Set(getTrainingModuleTitles());
    const trainingCourses = courses
      .filter((c) => c.status !== 'archived' && titles.has(c.name))
      .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.name.localeCompare(b.name));

    if (!trainingCourses.length) return fallback();

    return trainingCourses.map((c, i) => mapCourseToTrainingProgram(c, i));
  } catch {
    return fallback();
  }
}

// Trainings = Courses: registration and admin lists use active training programs only
export const getAllCourses = getTrainingCourses;
export const getCourseById = getCourse;