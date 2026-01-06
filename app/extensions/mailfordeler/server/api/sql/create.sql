-- Create email_events table for storing Postmark webhook events
CREATE TABLE IF NOT EXISTS email_events (
    id SERIAL PRIMARY KEY,
    assoc_db VARCHAR(255) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    message_id VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    occurred_at TIMESTAMP NOT NULL,
    details JSONB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes to improve query performance
CREATE INDEX IF NOT EXISTS idx_email_events_message_id ON email_events(message_id);
CREATE INDEX IF NOT EXISTS idx_email_events_asoc_db ON email_events(asoc_db);
CREATE INDEX IF NOT EXISTS idx_email_events_occurred_at ON email_events(occurred_at);

-- Add comment to describe the table
COMMENT ON TABLE email_events IS 'Stores email events from Postmark webhooks including bounces, deliveries, opens, and spam complaints';

-- Add comments for columns
COMMENT ON COLUMN email_events.asoc_db IS 'Asociated database identifier';
COMMENT ON COLUMN email_events.event_type IS 'Type of email event (bounce, delivery, open, spam_complaint)';
COMMENT ON COLUMN email_events.message_id IS 'Unique identifier for the email from Postmark';
COMMENT ON COLUMN email_events.email IS 'Email address of the recipient';
COMMENT ON COLUMN email_events.occurred_at IS 'Timestamp when the event occurred';
COMMENT ON COLUMN email_events.details IS 'Full JSON payload of the webhook event';
COMMENT ON COLUMN email_events.created_at IS 'Timestamp when the record was created';