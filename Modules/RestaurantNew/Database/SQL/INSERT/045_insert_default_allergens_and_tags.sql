-- Run per business by replacing :business_id if your SQL client does not support variables.
INSERT INTO restaurant_new_allergens (business_id, name, code, severity_level, requires_customer_warning, is_active, created_at, updated_at)
SELECT :business_id, 'Peanuts', 'PEANUTS', 'critical', 1, 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_allergens WHERE business_id=:business_id AND name='Peanuts');
INSERT INTO restaurant_new_allergens (business_id, name, code, severity_level, requires_customer_warning, is_active, created_at, updated_at)
SELECT :business_id, 'Dairy', 'DAIRY', 'warning', 1, 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_allergens WHERE business_id=:business_id AND name='Dairy');
INSERT INTO restaurant_new_allergens (business_id, name, code, severity_level, requires_customer_warning, is_active, created_at, updated_at)
SELECT :business_id, 'Gluten', 'GLUTEN', 'warning', 1, 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_allergens WHERE business_id=:business_id AND name='Gluten');
INSERT INTO restaurant_new_dietary_tags (business_id, name, code, tag_type, is_active, created_at, updated_at)
SELECT :business_id, 'Vegetarian', 'VEG', 'dietary', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_dietary_tags WHERE business_id=:business_id AND name='Vegetarian');
INSERT INTO restaurant_new_dietary_tags (business_id, name, code, tag_type, is_active, created_at, updated_at)
SELECT :business_id, 'Vegan', 'VEGAN', 'dietary', 1, NOW(), NOW() WHERE NOT EXISTS (SELECT 1 FROM restaurant_new_dietary_tags WHERE business_id=:business_id AND name='Vegan');
