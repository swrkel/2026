/* HOTELMGT_001 - Hotel Management tenant/business scope support
   Run on every tenant database that uses the Hotel Management module.
   These queries are written as MySQL 8+ idempotent ADD COLUMN IF NOT EXISTS statements.
*/
ALTER TABLE hm_buildings ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_wings ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_floors ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_amenities ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_room_types ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_rooms ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_room_features ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_rate_plans ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_seasons ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_guests ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_reservations ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_reservation_rooms ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_checkins ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_checkouts ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_deposits ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_folio_lines ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_store_movements ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_guest_preferences ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE hm_guest_notes ADD COLUMN IF NOT EXISTS business_id BIGINT UNSIGNED NULL AFTER id, ADD COLUMN IF NOT EXISTS business_location_id BIGINT UNSIGNED NULL AFTER business_id;

CREATE INDEX IF NOT EXISTS hm_buildings_business_id_index ON hm_buildings (business_id);
CREATE INDEX IF NOT EXISTS hm_rooms_business_id_index ON hm_rooms (business_id);
CREATE INDEX IF NOT EXISTS hm_guests_business_id_index ON hm_guests (business_id);
CREATE INDEX IF NOT EXISTS hm_reservations_business_id_index ON hm_reservations (business_id);
CREATE INDEX IF NOT EXISTS hm_folios_business_id_index ON hm_folios (business_id);
