-- CommunicationHub rollback SQL.
-- WARNING: This removes CommunicationHub tenant data. Use only after a full backup.

DROP TABLE IF EXISTS communication_hub_api_request_logs;
DROP TABLE IF EXISTS communication_hub_api_clients;
DROP TABLE IF EXISTS communication_hub_marketplace_packages;
DROP TABLE IF EXISTS communication_hub_campaigns;
DROP TABLE IF EXISTS communication_hub_audit_logs;
DROP TABLE IF EXISTS communication_hub_otps;
DROP TABLE IF EXISTS communication_hub_delivery_events;
DROP TABLE IF EXISTS communication_hub_messages;
DROP TABLE IF EXISTS communication_hub_templates;
DROP TABLE IF EXISTS communication_hub_providers;
DROP TABLE IF EXISTS communication_hub_settings;
