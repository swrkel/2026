/* HOTELMGT_002 support SQL - run per tenant database only if HOTELMGT_001 business-scope columns were not applied. */

ALTER TABLE hm_guests ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE hm_guests ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_reservations ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE hm_reservations ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_rate_plans ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE hm_rate_plans ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_rooms ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE hm_rooms ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_checkins ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE hm_checkins ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_checkouts ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE hm_checkouts ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;

CREATE INDEX IF NOT EXISTS hm_guests_business_idx ON hm_guests (business_id);
CREATE INDEX IF NOT EXISTS hm_reservations_business_idx ON hm_reservations (business_id);
CREATE INDEX IF NOT EXISTS hm_rooms_business_idx ON hm_rooms (business_id);
