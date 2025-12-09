-- Database Initialization for Email Interaction Tracker

CREATE TABLE IF NOT EXISTS email_opens (
    id SERIAL PRIMARY KEY,
    tracking_id VARCHAR(255) NOT NULL,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT,
    ip_address VARCHAR(45) -- Supports IPv6
);

-- Index for faster lookups on tracking_id
CREATE INDEX idx_tracking_id ON email_opens(tracking_id);
