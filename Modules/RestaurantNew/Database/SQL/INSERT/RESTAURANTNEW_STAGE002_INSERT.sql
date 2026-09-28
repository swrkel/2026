-- Optional idempotent default data. Replace @business_id and @location_id before running.
INSERT INTO rn_order_types (business_id, location_id, name, slug, requires_table, requires_customer, allow_delivery, sort_order, is_active, created_at, updated_at)
SELECT @business_id, @location_id, x.name, x.slug, x.requires_table, x.requires_customer, x.allow_delivery, x.sort_order, 1, NOW(), NOW()
FROM (
  SELECT 'Dine In' name, 'dine_in' slug, 1 requires_table, 0 requires_customer, 0 allow_delivery, 1 sort_order UNION ALL
  SELECT 'Takeaway', 'takeaway', 0, 0, 0, 2 UNION ALL
  SELECT 'Delivery', 'delivery', 0, 1, 1, 3
) x
WHERE NOT EXISTS (SELECT 1 FROM rn_order_types r WHERE r.business_id=@business_id AND ((r.location_id <=> @location_id)) AND r.slug=x.slug);

INSERT INTO rn_numbering_sequences (business_id, location_id, document_type, prefix, next_number, padding, created_at, updated_at)
SELECT @business_id, @location_id, x.document_type, x.prefix, 1, 5, NOW(), NOW()
FROM (
  SELECT 'order' document_type, 'ORD-' prefix UNION ALL
  SELECT 'kot', 'KOT-' UNION ALL
  SELECT 'bill', 'BILL-'
) x
WHERE NOT EXISTS (SELECT 1 FROM rn_numbering_sequences r WHERE r.business_id=@business_id AND ((r.location_id <=> @location_id)) AND r.document_type=x.document_type);
