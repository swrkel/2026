-- Optional CommunicationHub performance indexes.
-- Run after the main table creation SQL if report/query performance needs improvement.

CREATE INDEX IF NOT EXISTS idx_ch_messages_business_status ON communication_hub_messages (business_id, status);
CREATE INDEX IF NOT EXISTS idx_ch_messages_business_channel ON communication_hub_messages (business_id, channel);
CREATE INDEX IF NOT EXISTS idx_ch_messages_created_at ON communication_hub_messages (created_at);
CREATE INDEX IF NOT EXISTS idx_ch_delivery_events_message ON communication_hub_delivery_events (message_id);
CREATE INDEX IF NOT EXISTS idx_ch_templates_business_channel ON communication_hub_templates (business_id, channel);
