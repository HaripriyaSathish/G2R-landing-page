-- G2R Global Solutions landing page — PostgreSQL schema (Neon / Render).
-- Mirrors database/schema.sql (MySQL). Timestamps are local time (database timezone Asia/Kolkata).

CREATE OR REPLACE FUNCTION g2r_touch_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL DEFAULT 'Administrator',
    email VARCHAR(190) NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(100) PRIMARY KEY,
    svalue TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS media (
    id SERIAL PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NOT NULL,
    size INTEGER NOT NULL DEFAULT 0 CHECK (size >= 0),
    width INTEGER NULL,
    height INTEGER NULL,
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sections (
    id SERIAL PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active SMALLINT NOT NULL DEFAULT 1,
    data TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS section_items (
    id SERIAL PRIMARY KEY,
    section_slug VARCHAR(60) NOT NULL,
    grp VARCHAR(60) NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active SMALLINT NOT NULL DEFAULT 1,
    data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_section_grp ON section_items (section_slug, grp, sort_order);

CREATE TABLE IF NOT EXISTS enquiries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    enquiry_type VARCHAR(150) NULL,
    message TEXT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'new' CHECK (status IN ('new','read','replied','archived')),
    notes TEXT NULL,
    ip VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    mail_status VARCHAR(10) NOT NULL DEFAULT 'pending' CHECK (mail_status IN ('pending','sent','failed','skipped')),
    mail_error VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_status ON enquiries (status);
CREATE INDEX IF NOT EXISTS idx_ip_time ON enquiries (ip, created_at);

-- MySQL's "ON UPDATE CURRENT_TIMESTAMP" equivalent
DROP TRIGGER IF EXISTS trg_settings_touch ON settings;
CREATE TRIGGER trg_settings_touch BEFORE UPDATE ON settings FOR EACH ROW EXECUTE FUNCTION g2r_touch_updated_at();
DROP TRIGGER IF EXISTS trg_sections_touch ON sections;
CREATE TRIGGER trg_sections_touch BEFORE UPDATE ON sections FOR EACH ROW EXECUTE FUNCTION g2r_touch_updated_at();
DROP TRIGGER IF EXISTS trg_section_items_touch ON section_items;
CREATE TRIGGER trg_section_items_touch BEFORE UPDATE ON section_items FOR EACH ROW EXECUTE FUNCTION g2r_touch_updated_at();
