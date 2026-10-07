-- Supabase Schema for Digital Invitations Module

-- Events Table
CREATE TABLE IF NOT EXISTS public.events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    date DATE,
    time TIME,
    location VARCHAR(255),
    banner_url TEXT,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Invitation Templates Table
CREATE TABLE IF NOT EXISTS public.invitation_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    image_url TEXT NOT NULL,
    design_type VARCHAR(50) DEFAULT 'standard',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Invitations Table
CREATE TABLE IF NOT EXISTS public.invitations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID REFERENCES public.events(id) ON DELETE CASCADE,
    guest_name VARCHAR(255) NOT NULL,
    guest_phone VARCHAR(50) NOT NULL,
    guest_email VARCHAR(255),
    invitation_id VARCHAR(50) UNIQUE NOT NULL,
    invitation_category VARCHAR(50) DEFAULT 'Standard',
    template_id UUID REFERENCES public.invitation_templates(id) ON DELETE SET NULL,
    qr_code_url TEXT,
    invitation_image_url TEXT,
    invitation_pdf_url TEXT,
    whatsapp_message_id TEXT,
    delivery_status VARCHAR(50) DEFAULT 'Pending',
    checked_in BOOLEAN DEFAULT FALSE,
    checked_in_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_invitations_event_id ON public.invitations(event_id);
CREATE INDEX IF NOT EXISTS idx_invitations_invitation_id ON public.invitations(invitation_id);
CREATE INDEX IF NOT EXISTS idx_invitations_delivery_status ON public.invitations(delivery_status);

-- RLS Policies (Assuming auth.users contains admin roles, simplified for example)
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invitations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invitation_templates ENABLE ROW LEVEL SECURITY;

-- Allow public read access to active events (optional, depending on requirements)
CREATE POLICY "Allow public read events" ON public.events FOR SELECT USING (true);

-- Admins can do everything on events
CREATE POLICY "Admins full access events" ON public.events FOR ALL USING (auth.role() = 'authenticated');

-- Admins can do everything on invitations
CREATE POLICY "Admins full access invitations" ON public.invitations FOR ALL USING (auth.role() = 'authenticated');

-- Guests can view their own invitation by invitation_id (if not using auth for guests)
CREATE POLICY "Guests can view own invitation" ON public.invitations FOR SELECT USING (true);