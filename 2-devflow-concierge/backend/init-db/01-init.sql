-- Create the client_requirements table with a JSONB column
CREATE TABLE IF NOT EXISTS client_requirements (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    service_type VARCHAR(50),
    data JSONB NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Index for searching by session_id
CREATE INDEX IF NOT EXISTS idx_client_requirements_session_id ON client_requirements(session_id);