ALTER TABLE restaurant_new_equipment_assets ADD INDEX rn_eq_status_idx (business_id, location_id, status);
ALTER TABLE restaurant_new_equipment_work_orders ADD INDEX rn_wo_status_idx (business_id, location_id, status, priority);
ALTER TABLE restaurant_new_equipment_spare_parts ADD INDEX rn_spare_reorder_idx (business_id, location_id, current_stock, reorder_level);
