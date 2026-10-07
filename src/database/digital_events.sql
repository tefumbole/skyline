-- Digital Events Module Schema

CREATE TABLE IF NOT EXISTS public.events (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    location VARCHAR(255),
    banner_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc', now()),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc', now())
);

CREATE TABLE IF NOT EXISTS public.invitation_templates (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    html_content TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc', now())
);

CREATE TABLE IF NOT EXISTS public.whatsapp_templates (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    message_body TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc', now())
);

CREATE TABLE IF NOT EXISTS public.invitations (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    invitation_code VARCHAR(50) UNIQUE NOT NULL,
    event_id UUID REFERENCES public.events(id) ON DELETE CASCADE,
    guest_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    category VARCHAR(50) DEFAULT 'Standard',
    status VARCHAR(50) DEFAULT 'Pending',
    qr_code_url TEXT,
    card_image_url TEXT,
    checked_in BOOLEAN DEFAULT FALSE,
    checked_in_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc', now())
);

CREATE TABLE IF NOT EXISTS public.event_analytics (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    event_id UUID REFERENCES public.events(id) ON DELETE CASCADE,
    total_invitations INT DEFAULT 0,
    total_checked_in INT DEFAULT 0,
    last_updated TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc', now())
);

-- Enable RLS
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invitations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invitation_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.whatsapp_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.event_analytics ENABLE ROW LEVEL SECURITY;

-- Create Policies (Admin full access)
CREATE POLICY "Admins manage all events" ON public.events
    FOR ALL USING (auth.jwt() ->> 'role' IN ('admin', 'super_admin'));

CREATE POLICY "Admins manage all invitations" ON public.invitations
    FOR ALL USING (auth.jwt() ->> 'role' IN ('admin', 'super_admin'));

CREATE POLICY "Admins manage templates" ON public.invitation_templates
    FOR ALL USING (auth.jwt() ->> 'role' IN ('admin', 'super_admin'));

CREATE POLICY "Admins manage WA templates" ON public.whatsapp_templates
    FOR ALL USING (auth.jwt() ->> 'role' IN ('admin', 'super_admin'));

CREATE POLICY "Admins view analytics" ON public.event_analytics
    FOR ALL USING (auth.jwt() ->> 'role' IN ('admin', 'super_admin'));