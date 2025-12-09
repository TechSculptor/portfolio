CREATE TABLE IF NOT EXISTS requirements (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(255),
    service_type VARCHAR(50),
    requirements_json JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
