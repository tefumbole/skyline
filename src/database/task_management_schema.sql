-- ======================================================================================
-- TASK MANAGEMENT SYSTEM SCHEMA
-- ======================================================================================

-- --------------------------------------------------------------------------------------
-- 1. TABLES & COMMENTS
-- --------------------------------------------------------------------------------------

-- TASKS TABLE
-- Stores the main definition of tasks to be assigned to users.
CREATE TABLE IF NOT EXISTS public.tasks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title TEXT NOT NULL,
    description TEXT,
    priority TEXT DEFAULT 'Medium' CHECK (priority IN ('Low', 'Medium', 'High', 'Critical')),
    start_date DATE,
    deadline DATE NOT NULL,
    status TEXT DEFAULT 'Pending' CHECK (status IN ('Pending', 'In Progress', 'Completed', 'Overdue')),
    created_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

COMMENT ON TABLE public.tasks IS 'Core tasks definition created by admins/directors.';
COMMENT ON COLUMN public.tasks.title IS 'Short title of the task.';
COMMENT ON COLUMN public.tasks.description IS 'Detailed instructions for the task.';
COMMENT ON COLUMN public.tasks.priority IS 'Priority level: Low, Medium, High, Critical.';
COMMENT ON COLUMN public.tasks.deadline IS 'Date by which the task must be completed.';

-- TASK ASSIGNMENTS TABLE
-- Links tasks to specific users (profiles) and tracks their individual progress.
CREATE TABLE IF NOT EXISTS public.task_assignments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES public.tasks(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    status TEXT DEFAULT 'Pending' CHECK (status IN ('Pending', 'In Progress', 'Completed', 'Overdue')),
    progress INTEGER DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
    accepted_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(task_id, user_id)
);

COMMENT ON TABLE public.task_assignments IS 'Assigns tasks to users and tracks their individual progress/status.';
COMMENT ON COLUMN public.task_assignments.user_id IS 'Reference to the assigned user (auth.users via profiles).';
COMMENT ON COLUMN public.task_assignments.progress IS 'Percentage of completion from 0 to 100.';

-- TASK UPDATES TABLE
-- Tracks specific updates/comments made by assignees on their tasks.
CREATE TABLE IF NOT EXISTS public.task_updates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    assignment_id UUID NOT NULL REFERENCES public.task_assignments(id) ON DELETE CASCADE,
    progress INTEGER NOT NULL CHECK (progress >= 0 AND progress <= 100),
    comment TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

COMMENT ON TABLE public.task_updates IS 'Log of progress updates and comments provided by assignees.';
COMMENT ON COLUMN public.task_updates.comment IS 'Optional text detailing what was accomplished in this update.';

-- TASK ATTACHMENTS TABLE
-- Stores metadata about files uploaded alongside task updates.
CREATE TABLE IF NOT EXISTS public.task_attachments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES public.tasks(id) ON DELETE CASCADE,
    update_id UUID REFERENCES public.task_updates(id) ON DELETE CASCADE,
    file_name TEXT NOT NULL,
    file_url TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

COMMENT ON TABLE public.task_attachments IS 'Files attached to specific tasks or updates.';
COMMENT ON COLUMN public.task_attachments.file_url IS 'Publicly accessible URL of the file stored in Supabase Storage.';

-- TASK REMINDERS TABLE
-- Tracks automated reminders set for specific tasks.
CREATE TABLE IF NOT EXISTS public.task_reminders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    task_id UUID NOT NULL REFERENCES public.tasks(id) ON DELETE CASCADE,
    reminder_time TIMESTAMPTZ NOT NULL,
    is_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

COMMENT ON TABLE public.task_reminders IS 'Schedules and tracks automated notifications for impending tasks.';

-- --------------------------------------------------------------------------------------
-- 2. INDEXES FOR PERFORMANCE
-- --------------------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_tasks_status ON public.tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_deadline ON public.tasks(deadline);
CREATE INDEX IF NOT EXISTS idx_tasks_created_by ON public.tasks(created_by);

CREATE INDEX IF NOT EXISTS idx_task_assign_user ON public.task_assignments(user_id);
CREATE INDEX IF NOT EXISTS idx_task_assign_task ON public.task_assignments(task_id);
CREATE INDEX IF NOT EXISTS idx_task_assign_status ON public.task_assignments(status);

CREATE INDEX IF NOT EXISTS idx_task_updates_assignment ON public.task_updates(assignment_id);

CREATE INDEX IF NOT EXISTS idx_task_attach_task ON public.task_attachments(task_id);

CREATE INDEX IF NOT EXISTS idx_task_reminders_task ON public.task_reminders(task_id);
CREATE INDEX IF NOT EXISTS idx_task_reminders_time ON public.task_reminders(reminder_time);

-- --------------------------------------------------------------------------------------
-- 3. UPDATED_AT TRIGGERS
-- --------------------------------------------------------------------------------------
-- Assuming public.set_updated_at() exists from existing schema
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'trg_tasks_updated_at') THEN
        CREATE TRIGGER trg_tasks_updated_at
        BEFORE UPDATE ON public.tasks
        FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'trg_task_assignments_updated_at') THEN
        CREATE TRIGGER trg_task_assignments_updated_at
        BEFORE UPDATE ON public.task_assignments
        FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();
    END IF;
END $$;

-- --------------------------------------------------------------------------------------
-- 4. ROW LEVEL SECURITY (RLS)
-- --------------------------------------------------------------------------------------
ALTER TABLE public.tasks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_assignments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_updates ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.task_reminders ENABLE ROW LEVEL SECURITY;

-- Helper to check if current user is admin/director
CREATE OR REPLACE FUNCTION public.is_task_admin()
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM public.profiles 
        WHERE id = auth.uid() AND role IN ('admin', 'director')
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- TASKS POLICIES
CREATE POLICY "Admins can do everything on tasks" 
ON public.tasks FOR ALL 
USING (public.is_task_admin())
WITH CHECK (public.is_task_admin());

CREATE POLICY "Users can view tasks assigned to them" 
ON public.tasks FOR SELECT 
USING (
    EXISTS (
        SELECT 1 FROM public.task_assignments 
        WHERE task_assignments.task_id = tasks.id AND task_assignments.user_id = auth.uid()
    )
);

-- TASK ASSIGNMENTS POLICIES
CREATE POLICY "Admins can do everything on assignments" 
ON public.task_assignments FOR ALL 
USING (public.is_task_admin())
WITH CHECK (public.is_task_admin());

CREATE POLICY "Users can view their own assignments" 
ON public.task_assignments FOR SELECT 
USING (user_id = auth.uid());

CREATE POLICY "Users can update their own assignments" 
ON public.task_assignments FOR UPDATE 
USING (user_id = auth.uid())
WITH CHECK (user_id = auth.uid());

-- TASK UPDATES POLICIES
CREATE POLICY "Admins can do everything on updates" 
ON public.task_updates FOR ALL 
USING (public.is_task_admin())
WITH CHECK (public.is_task_admin());

CREATE POLICY "Users can view updates for their assignments" 
ON public.task_updates FOR SELECT 
USING (
    EXISTS (
        SELECT 1 FROM public.task_assignments 
        WHERE task_assignments.id = task_updates.assignment_id AND task_assignments.user_id = auth.uid()
    )
);

CREATE POLICY "Users can insert updates for their assignments" 
ON public.task_updates FOR INSERT 
WITH CHECK (
    EXISTS (
        SELECT 1 FROM public.task_assignments 
        WHERE task_assignments.id = assignment_id AND task_assignments.user_id = auth.uid()
    )
);

-- TASK ATTACHMENTS POLICIES
CREATE POLICY "Admins can do everything on attachments" 
ON public.task_attachments FOR ALL 
USING (public.is_task_admin())
WITH CHECK (public.is_task_admin());

CREATE POLICY "Users can view attachments for their tasks" 
ON public.task_attachments FOR SELECT 
USING (
    EXISTS (
        SELECT 1 FROM public.task_assignments 
        WHERE task_assignments.task_id = task_attachments.task_id AND task_assignments.user_id = auth.uid()
    )
);

CREATE POLICY "Users can insert attachments for their tasks" 
ON public.task_attachments FOR INSERT 
WITH CHECK (
    EXISTS (
        SELECT 1 FROM public.task_assignments 
        WHERE task_assignments.task_id = task_id AND task_assignments.user_id = auth.uid()
    )
);

-- TASK REMINDERS POLICIES
CREATE POLICY "Admins can do everything on reminders" 
ON public.task_reminders FOR ALL 
USING (public.is_task_admin())
WITH CHECK (public.is_task_admin());


-- --------------------------------------------------------------------------------------
-- 5. STORAGE BUCKET CONFIGURATION & POLICIES
-- --------------------------------------------------------------------------------------

-- Insert the bucket if it doesn't exist
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
    'task-attachments', 
    'task-attachments', 
    TRUE, 
    52428800, -- 50MB
    ARRAY['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel']
)
ON CONFLICT (id) DO NOTHING;

-- Storage Policies for task-attachments bucket
CREATE POLICY "Public Access Task Attachments"
ON storage.objects FOR SELECT
USING (bucket_id = 'task-attachments');

CREATE POLICY "Authenticated Upload Task Attachments"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'task-attachments' AND auth.role() = 'authenticated');

CREATE POLICY "Authenticated Update Task Attachments"
ON storage.objects FOR UPDATE
USING (bucket_id = 'task-attachments' AND auth.role() = 'authenticated');

CREATE POLICY "Authenticated Delete Task Attachments"
ON storage.objects FOR DELETE
USING (bucket_id = 'task-attachments' AND auth.role() = 'authenticated');