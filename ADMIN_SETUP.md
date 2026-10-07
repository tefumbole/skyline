# Admin Panel Setup Guide

## 1. Authentication Configuration

The system uses Supabase Authentication protected by a custom `admin_users` table and Role-Based Access Control (RBAC).

### Database Setup
Ensure you have run the migration script in `src/supabase_schema.sql` to create the required tables:
- `admin_users`: Stores admin profiles
- `auth_login_logs`: Tracks login attempts
- `handle_new_admin_user`: Trigger function to auto-assign admin role

### Creating the First Admin User
1. Go to your **Supabase Dashboard** -> **Authentication** -> **Users**.
2. Click **Add User** -> **Create New User**.
3. **Important**: You MUST use the email `admin@alphabridge.com`. The system is hardcoded to only grant admin privileges to this email address automatically via the database trigger.
4. Set the password to the default temporary password: `Admin@123456`.
5. Check "Auto-confirm user" to bypass email verification for this setup.
6. Click **Create User**.

*Note: The database trigger `on_auth_user_created` will automatically insert a record into the `admin_users` table with the role 'admin' for this email.*

## 2. Accessing the Admin Portal

- **Login URL**: `/admin/login`
- **Email**: `admin@alphabridge.com`
- **Password**: `Admin@123456`

**Security Recommendation**: Immediately after logging in, please change your password via the Supabase Dashboard or request a password reset email if email service is configured.

## 3. Protected Routes

The following routes are secured by `ProtectedRoute.jsx` and require both a valid session AND an 'admin' role in the `admin_users` table:

- `/admin/dashboard` - Main Overview
- `/admin/students` - Master Class Registry
- `/admin/payments` - Payment Management
- `/admin/events` - Event Approvals
- `/admin/shareholders` - Investor Management

## 4. Troubleshooting

**Error: "Access Denied: You do not have admin privileges."**
- Check the `admin_users` table in Supabase.
- Ensure your `auth.uid` matches the `id` in `admin_users`.
- Ensure the `role` column is set to `admin`.
- If the record is missing, manually insert it into `admin_users` using the SQL Editor: