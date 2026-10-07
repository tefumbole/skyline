-- Audio Mixing Assistant Schema
-- Note: As the Supabase integration is incomplete, this is provided for reference.

CREATE TABLE IF NOT EXISTS instruments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS genres (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS mix_styles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS mixing_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    instrument_id UUID REFERENCES instruments(id) ON DELETE CASCADE,
    genre_id UUID REFERENCES genres(id) ON DELETE CASCADE,
    style_id UUID REFERENCES mix_styles(id) ON DELETE CASCADE,
    creator_id UUID,
    settings JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS template_ratings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    template_id UUID REFERENCES mixing_templates(id) ON DELETE CASCADE,
    user_id UUID NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    UNIQUE(template_id, user_id)
);

CREATE TABLE IF NOT EXISTS template_favorites (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    template_id UUID REFERENCES mixing_templates(id) ON DELETE CASCADE,
    user_id UUID NOT NULL,
    UNIQUE(template_id, user_id)
);

CREATE TABLE IF NOT EXISTS template_usage_tracking (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    template_id UUID REFERENCES mixing_templates(id) ON DELETE CASCADE,
    user_id UUID NOT NULL,
    used_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS mix_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    project_name VARCHAR(255),
    artist_name VARCHAR(255),
    genre_id UUID REFERENCES genres(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS mix_session_tracks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    session_id UUID REFERENCES mix_sessions(id) ON DELETE CASCADE,
    track_name VARCHAR(255) NOT NULL,
    instrument_id UUID REFERENCES instruments(id)
);

CREATE TABLE IF NOT EXISTS template_recommendations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    track_id UUID REFERENCES mix_session_tracks(id) ON DELETE CASCADE,
    template_id UUID REFERENCES mixing_templates(id) ON DELETE CASCADE,
    confidence_score FLOAT,
    reason TEXT
);

CREATE TABLE IF NOT EXISTS instrument_keywords (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    instrument_id UUID REFERENCES instruments(id) ON DELETE CASCADE,
    keyword VARCHAR(100) NOT NULL,
    weight FLOAT DEFAULT 1.0
);

CREATE TABLE IF NOT EXISTS template_presets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    template_id UUID REFERENCES mixing_templates(id) ON DELETE CASCADE,
    preset_data JSONB
);

CREATE TABLE IF NOT EXISTS admin_audio_settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value JSONB
);

CREATE TABLE IF NOT EXISTS audio_admin_roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL UNIQUE,
    role VARCHAR(50) DEFAULT 'audio_admin'
);