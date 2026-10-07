-- Enable UUID extension
create extension if not exists "uuid-ossp";

-- Table: shareholders
create table if not exists shareholders (
  id uuid default uuid_generate_v4() primary key,
  name text not null,
  email text not null,
  phone text not null,
  shares_assigned integer not null default 0,
  payment_status text not null default 'Pay Later',
  created_at timestamp with time zone default timezone('utc'::text, now()) not null,
  updated_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: members (Team/Staff)
create table if not exists members (
  id uuid default uuid_generate_v4() primary key,
  name text not null,
  title text not null,
  description text,
  photo_url text,
  email text,
  phone text,
  created_at timestamp with time zone default timezone('utc'::text, now()) not null,
  updated_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: share_tracking
create table if not exists share_tracking (
  id uuid default uuid_generate_v4() primary key,
  total_shares integer default 60,
  assigned_shares integer default 0,
  remaining_shares integer default 60,
  updated_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- TIMESHEET TABLES --

-- Table: activities
create table if not exists activities (
  id uuid default uuid_generate_v4() primary key,
  employee_id uuid references auth.users(id),
  activity_name text not null,
  description text,
  category text,
  created_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: timesheet_entries
create table if not exists timesheet_entries (
  id uuid default uuid_generate_v4() primary key,
  employee_id uuid references auth.users(id),
  date date not null,
  activity_id uuid references activities(id),
  hours decimal(5,2) not null check (hours >= 0 and hours <= 24),
  notes text,
  status text default 'pending', -- pending, approved, rejected
  created_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: working_week
create table if not exists working_week (
  id uuid default uuid_generate_v4() primary key,
  employee_id uuid references auth.users(id) unique,
  monday boolean default true,
  tuesday boolean default true,
  wednesday boolean default true,
  thursday boolean default true,
  friday boolean default true,
  saturday boolean default false,
  sunday boolean default false,
  expected_hours_per_day decimal(5,2) default 8.0,
  created_at timestamp with time zone default timezone('utc'::text, now()) not null,
  updated_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: overtime_tracking
create table if not exists overtime_tracking (
  id uuid default uuid_generate_v4() primary key,
  employee_id uuid references auth.users(id),
  week_start_date date not null,
  total_hours_worked decimal(10,2) default 0,
  expected_hours decimal(10,2) default 40,
  overtime_hours decimal(10,2) default 0,
  created_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- COMMUNICATION TABLES --

-- Table: communication_categories
create table if not exists communication_categories (
  id uuid default uuid_generate_v4() primary key,
  name text not null,
  description text,
  color text default '#3b82f6',
  created_at timestamp with time zone default timezone('utc'::text, now()) not null,
  updated_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: notifications
create table if not exists notifications (
  id uuid default uuid_generate_v4() primary key,
  title text not null,
  message text not null,
  category_id uuid references communication_categories(id),
  sender_id uuid references auth.users(id),
  sent_at timestamp with time zone default timezone('utc'::text, now()) not null,
  is_archived boolean default false
);

-- Table: notification_recipients
create table if not exists notification_recipients (
  id uuid default uuid_generate_v4() primary key,
  notification_id uuid references notifications(id) on delete cascade,
  recipient_id uuid not null, -- Can be user_id, shareholder_id, etc.
  recipient_type text not null, -- 'User', 'Shareholder', 'Member'
  is_read boolean default false,
  read_at timestamp with time zone,
  created_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- Table: letters
create table if not exists letters (
  id uuid default uuid_generate_v4() primary key,
  title text not null,
  content text not null,
  category_id uuid references communication_categories(id),
  sender_id uuid references auth.users(id),
  sent_at timestamp with time zone default timezone('utc'::text, now()) not null,
  is_archived boolean default false
);

-- Table: letter_recipients
create table if not exists letter_recipients (
  id uuid default uuid_generate_v4() primary key,
  letter_id uuid references letters(id) on delete cascade,
  recipient_id uuid not null,
  recipient_type text not null,
  is_read boolean default false,
  read_at timestamp with time zone,
  created_at timestamp with time zone default timezone('utc'::text, now()) not null
);

-- JOBS & APPLICATIONS TABLES --

-- Table: jobs
CREATE TABLE IF NOT EXISTS jobs (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    requirements TEXT,
    location TEXT,
    type TEXT,
    salary TEXT,
    status TEXT DEFAULT 'active',
    current_applicants INTEGER DEFAULT 0,
    posted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table: applications
CREATE TABLE IF NOT EXISTS applications (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    job_id UUID REFERENCES jobs(id) ON DELETE CASCADE,
    user_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    cv_url TEXT,
    cover_letter TEXT,
    status TEXT DEFAULT 'pending',
    reference_number TEXT,
    rejection_reason TEXT,
    interview_date TIMESTAMP WITH TIME ZONE,
    status_changed_at TIMESTAMP WITH TIME ZONE,
    status_changed_by UUID REFERENCES auth.users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table: application_status_history
CREATE TABLE IF NOT EXISTS application_status_history (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    application_id UUID REFERENCES applications(id) ON DELETE CASCADE,
    previous_status TEXT,
    new_status TEXT NOT NULL,
    changed_by UUID REFERENCES auth.users(id),
    reason TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- RLS Policies

alter table activities enable row level security;
alter table timesheet_entries enable row level security;
alter table working_week enable row level security;
alter table overtime_tracking enable row level security;
alter table communication_categories enable row level security;
alter table notifications enable row level security;
alter table notification_recipients enable row level security;
alter table letters enable row level security;
alter table letter_recipients enable row level security;
alter table jobs enable row level security;
alter table applications enable row level security;
alter table application_status_history enable row level security;

-- Activities Policies
create policy "Employees can view own activities" on activities for select using (auth.uid() = employee_id);
create policy "Employees can insert own activities" on activities for insert with check (auth.uid() = employee_id);
create policy "Employees can update own activities" on activities for update using (auth.uid() = employee_id);
create policy "Employees can delete own activities" on activities for delete using (auth.uid() = employee_id);
create policy "Admins can view all activities" on activities for select using (auth.jwt() ->> 'role' = 'admin');

-- TimeSheet Entries Policies
create policy "Employees can view own entries" on timesheet_entries for select using (auth.uid() = employee_id);
create policy "Employees can insert own entries" on timesheet_entries for insert with check (auth.uid() = employee_id);
create policy "Employees can update own entries" on timesheet_entries for update using (auth.uid() = employee_id);
create policy "Employees can delete own entries" on timesheet_entries for delete using (auth.uid() = employee_id);
create policy "Admins can view all entries" on timesheet_entries for select using (auth.jwt() ->> 'role' = 'admin');

-- Working Week Policies
create policy "Employees can view own working week" on working_week for select using (auth.uid() = employee_id);
create policy "Employees can manage own working week" on working_week for all using (auth.uid() = employee_id);
create policy "Admins can view all working weeks" on working_week for select using (auth.jwt() ->> 'role' = 'admin');

-- Overtime Policies
create policy "Employees can view own overtime" on overtime_tracking for select using (auth.uid() = employee_id);
create policy "Admins can view all overtime" on overtime_tracking for select using (auth.jwt() ->> 'role' = 'admin');

-- Communication Policies
-- Admins manage categories
create policy "Admins manage categories" on communication_categories for all using (auth.jwt() ->> 'role' = 'admin');
create policy "Everyone views categories" on communication_categories for select using (true);

-- Notification Policies
create policy "Admins manage notifications" on notifications for all using (auth.jwt() ->> 'role' = 'admin');
create policy "Recipients view their notifications" on notification_recipients for select using (auth.uid()::text = recipient_id::text); -- Casting to text for flexibility with IDs

-- Letter Policies
create policy "Admins manage letters" on letters for all using (auth.jwt() ->> 'role' = 'admin');
create policy "Recipients view their letters" on letter_recipients for select using (auth.uid()::text = recipient_id::text);

-- Jobs Policies
create policy "Public view active jobs" on jobs for select using (status = 'active');
create policy "Admins manage jobs" on jobs for all using (public.is_admin());

-- Applications Policies
create policy "Public insert applications" on applications for insert using (true);
create policy "Users view own applications" on applications for select using (auth.uid() = user_id);
create policy "Admins manage applications" on applications for all using (public.is_admin());

-- History Policies
create policy "Admins view history" on application_status_history for select using (public.is_admin());
create policy "Admins insert history" on application_status_history for insert using (public.is_admin());

-- Shareholders Policies
alter table shareholders enable row level security;

drop policy if exists "authenticated_select_shareholders" on shareholders;
create policy "authenticated_select_shareholders" on shareholders for select using (auth.role() = 'authenticated');